/**
 * Shop sort (template-parts/shop/toolbar.php): picking a sort submits the GET
 * form, which reloads the archive in that order. Without JavaScript the form's
 * <noscript> button does the same.
 */

export function init() {
  document.querySelectorAll('select[data-shop-sort]').forEach((select) => {
    select.addEventListener('change', () => {
      select.form?.submit();
    });
  });
}
