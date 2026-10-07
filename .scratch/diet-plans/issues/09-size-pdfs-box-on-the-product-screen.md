# "Size PDFs" box on the Product edit screen

Type: task
Status: claimed
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

### 2026-10-07 (Claude)

Built in the "Diets 09" commit, in `inc/shop/size-files.php` (loaded with the other shop files). The boxes stay open until the owner's PC run.

**How it works:**
- The box (`add_meta_boxes_product`, variable Products only) shows the warnings, the table in the picker's order (`optimum_lift_size_variations()`) and a `multiple` PDF field. `post_edit_form_tag` adds `multipart/form-data` on every Product form, which does no harm on Simple ones.
- On save it runs on `woocommerce_process_product_meta` at priority 50, after WooCommerce's own save (10) and images (20). It needs the nonce and `edit_product` on the post.
- Matching (`optimum_lift_size_files_plan()`): the stem is the name without `.pdf` and a trailing `kg`, and it must end with `-` plus the variation's values in the parent's attribute order. `…-90pluskg.pdf` works, since the renderer's code is `90plus`. Every problem is collected: not a PDF (`wp_check_filetype_and_ext()` with PDF only), no match, several matches, or two files for one slot. Nothing is stored unless the list is empty.
- Storing goes through `wp_handle_upload()` with an `upload_dir` filter pointing at `{uploads}/woocommerce_uploads/diets/<slug>`, WooCommerce's protected folder (its `.htaccess` sits at the top). A `unique_filename_callback` keeps the file's own name, so a re-upload overwrites it. A file that cannot be stored attaches none.
- Download IDs:
  - The same plan prefix in this box's folder keeps its ID.
  - Otherwise the new file takes over the ID of a download it replaces (for a diet, its one file; for example the seed's `ol-demo` placeholder or a hand-attached file).
  - Only a genuinely new slot gets `wp_generate_uuid4()`.
  - A bundle keeps this box's files for plans the upload does not touch. So a bundle can take its two plans in two uploads, which matters on a host whose `post_max_size` is below the 10 MB the 20 PDFs need (each rendered PDF is about 0.5 MB).
- Names: "<Product> — <Size>", and for a bundle " — <Plan>" from the prefix ("Djegie yndyre"). Variations are set Virtual and Downloadable.
- PDFs in the folder that no variation uses any more are deleted.
- Warnings, in the box and as a notice after save:
  - a different price;
  - not Virtual;
  - not Downloadable or no file;
  - an "Any …" attribute;
  - for a bundle, fewer files than its sized components.

  Mapping a file to a particular component is not possible, because plan slugs are not Product slugs. So the bundle check counts files against sized components.

**PC test:**
1. Render `node render.js plans/djegie-yndyre.json` and `plans/djegie-e-shpejte.json` in `content/diets`.
2. Product › 12-javor:
   - Before uploading, print the download IDs: `wp eval '$v = wc_get_product(<variation ID>); print_r(array_keys($v->get_downloads()));'`
   - Upload the 10 `djegie-yndyre` PDFs and Update.
   - Expect "10 PDFs attached.", a table with no warning, and the same IDs as before.
   - Open three files from My Account or the table and check each cover's Size.
3. `dieta-mesdhetare`: the 5 `djegie-e-shpejte` PDFs match by weight.
4. Error cases: rename one file to `x.pdf`, then add a second copy of one Size. Each time, nothing is attached and the notice names the files.
5. Place a test order and download the file. Re-upload one changed PDF. The same link now serves the new file, and `wp-content/uploads/woocommerce_uploads/diets/plani-ushqimor-12-javor/` holds 10 files.
6. Bundle: upload all 20 files. Each variation lists two.
7. Warnings: change one variation's price, untick Virtual on another, and remove the file from a third. All three warnings show.
8. Refusals:
   - a `.txt` renamed to `.pdf` is refused;
   - a Shop Manager can upload;
   - a user without `edit_product` never reaches the save.
