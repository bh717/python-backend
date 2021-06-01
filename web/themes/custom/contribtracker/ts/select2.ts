(function ($): void {
  jQuery(function () {
    // @ts-ignore
    $('.form-select').select2();
    document
      .querySelectorAll('.select2-selection--single')
      .forEach((element) => {
        element
          ?.querySelector('.select2-selection__rendered')
          ?.classList.add('select2-selection--single__rendered');
      });
  });
})(jQuery);
