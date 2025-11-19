(function ($) {
  $(function () {
    $('.dybot-copy').on('click', function () {
      const target = $('#' + $(this).data('target'));
      if (!target.length) {
        return;
      }
      target.trigger('select');
      document.execCommand('copy');
      wp.data && wp.data.dispatch('core/notices').createNotice('success', dybotAdmin.copiedText, {
        type: 'snackbar',
      });
    });
  });
})(jQuery);
