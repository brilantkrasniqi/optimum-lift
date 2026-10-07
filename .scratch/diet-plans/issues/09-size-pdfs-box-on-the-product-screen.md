# "Size PDFs" box on the Product edit screen

Type: task
Status: ready-for-agent
Blocked by: 05

## What to build

Read spec Decision 19. The owner gets 5 or 10 PDFs per diet from the renderer, named `<plan>-<gender>-<weight>kg.pdf`. This box puts each on the right variation in one upload, and shows whether each Size is ready to sell. It is admin-only theme code in a new `inc/shop/size-files.php`.

**The box** shows on variable Products only, on the Product edit screen, titled "Size PDFs":
- **A table, one row per variation:** the Size label, price, Virtual, Downloadable, its file names, and status. Rows are in the picker's order.
- **Warnings**, above the table and as an admin notice after save, the way `bundle.php` warns about a bundle that saves nothing:
  - a variation's price differs from the others (Decision 10);
  - a variation is not Virtual (checkout would ask for an address and skip the withdrawal waiver, `withdrawal.php:30`);
  - a variation is not Downloadable or has no file;
  - a variation has an "Any …" attribute;
  - for a bundle, a variation lacks a file for one of its sized components.
- **An upload field:** `multiple`, PDFs only. The post form gets `enctype="multipart/form-data"` through `post_edit_form_tag`, and files are processed on save after WooCommerce has saved the Product.
- Capability `edit_product` on that post, plus a nonce.

**Matching a file to a variation:**
1. Strip `.pdf` and a trailing `kg`.
2. The rest must end with `-` plus the variation's attribute values joined by `-` in the parent's attribute order.

So `djegie-e-shpejte-mashkull-80-90kg.pdf` matches a Gjinia × Pesha variation `mashkull`/`80-90`, and also a Pesha-only variation `80-90`, because the men-only renderer still writes `mashkull` into the name. The part before the match is the file's **plan prefix**.

- For a diet, each variation takes at most one file.
- For a bundle, each variation takes one file per plan prefix (one per component diet).
- The upload is all or nothing: a file that matches no variation, matches several, or collides with another file for the same slot attaches nothing. The error lists every problem file.

**Attaching:**
- Store the files under `woocommerce_uploads/diets/<product-slug>/`, WooCommerce's protected folder. Reuse WooCommerce's own upload-directory handling rather than writing paths by hand.
- A re-upload overwrites the same path.
- Each variation's download keeps its download ID: reuse the ID of the existing download with the same plan prefix, else make a new one. Customers who already bought then get the new file through their existing link.
- The download is named "<Product name> — <Size label>", plus the plan's name for a bundle file.
- Set the variation Virtual and Downloadable.

## Acceptance criteria

- [ ] Uploading the 10 PDFs from `content/diets/out/djegie-yndyre/` (or the shared folder's copies) to `plani-ushqimor-12-javor` puts each file on the variation its name says. Open three at random and check the cover's Size. All 10 are Virtual and Downloadable, and the table shows no warning.
- [ ] Uploading the 5 `djegie-e-shpejte` PDFs to `dieta-mesdhetare` (Pesha only) matches by weight.
- [ ] A misnamed file, or two files for one Size, attaches nothing, and the error names the files.
- [ ] Re-uploading after a test order:
  - the variation's download ID is unchanged (`wp eval` printing `array_keys($variation->get_downloads())` before and after);
  - the Customer's existing link serves the new file;
  - no stray copies are left in the folder.
- [ ] The bundle takes 20 files (two plans) and each variation ends with two.
- [ ] Changing one variation's price, unticking Virtual on another, and removing a file from a third shows all three warnings.
- [ ] A non-PDF is refused, and a user without `edit_product` on the post cannot upload.
- [ ] `npm run lint:php` and `npm run analyse:php` pass.

## Comments
