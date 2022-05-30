/**
 * Function to handle alert sound at chat
 */
define([
      'jquery',
      'TYPO3/CMS/Backend/Notification'
    ],
    function ($, Notification) {
      'use strict';

      let AlertSound = function() {
        let me = this;
        let extKey = 'supportchat';

        me.init = function() {
          $('#alert-select').on('change', function() {
            me.setAlertSound($(this).val());
          })
        };

        me.setAlertSound = function(alertSound) {
          $.ajax({
            type: "POST",
            url: TYPO3.settings.ajaxUrls['alert_sound'],
            data: "alertSound=" + alertSound,
            success: function(response) {
              if (response.success == 'true') {
                let url = $('#beep_alert > source').attr('src');
                $('#beep_alert > source').attr(
                    'src',
                    url.replace(/[\w\-]*.ogg/g, response.sound)
                );
                // Reload page to load new sound
                window.location.href = window.location.href;
                $('#beep_alert').get(0).play(1);
                Notification.success(
                    'Alert sound',
                    'Alert sound successfully changed to ' + response.sound
                );
              }
            },
            error: function(response) {
              var r = response.responseText;
              Notification.error('Alert sound', r.message, 0);
            }
          });
        };
      };

      $(document).ready(function() {
        let alert = new AlertSound();
        alert.init();
      });
});
