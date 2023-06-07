$(function() {
    $('.js-createDemoAccess').click(function() {
        if ($(this).attr('data-ga-send')) {
            $(this).btnSpinner(
                ajaxToComponentAjax('desk:demo.clients', 'addDemoUser',
                    $(this.form).serializeArray().concat({name: 'sessid', value: getBitrixSessid()}),
                    function(response) {
                        if (response.status == 'error') {
                            alert(response.errors[1].message);
                        } else {
                            location.href = location.pathname;
                        }
                    },
                    function(response) {
                        if (response.status == 'error') {
                            alert(response.errors[1].message);
                        }
                    }
                )
            );
        }
        return false;
    });

    if ($('.FancyModal--createDemoAccess').length) {
        setTimeout(function() {
            $.fancybox({
                    content: $('.FancyModal--createDemoAccess').clone(true, true),
                    padding: 0,
                    showCloseButton: false
                }
            );
            initCopy();
        });
    }

    $('.js-filter--discount').mask('99');
    $('.js-filter--discount').keyup(function(){
        if (this.value == '') {
            this.value = '0';
        }
    })

    // копирование логина/пароля демо-пользователя
    if (
        $('.js-copy').length
        && typeof (document.queryCommandSupported) == 'function'
        && document.queryCommandSupported("copy") && document.queryCommandSupported("cut")
    ) {
        window.initCopy = function() {
            $('.js-copy').each(function() {
                var obButton = $(this);
                var clipboard = new Clipboard(this, {
                    text: function() {
                        var message = ' ';
                        if (obButton.attr('js-value-login-password') != null) {
                            message = obButton.attr('js-value-login-password');
                        }
                        return message;
                    }
                });
                if (typeof (clipboard) == 'object') {
                    obButton.parent().show();
                    clipboard.on('success', function(e) {
                        if (!obButton.hasClass('js-fancybox-close')) {
                            obButton.tipTip({
                                content: 'Скопировано!',
                                activation: 'manual',
                                theme: 'white',
                                afterEnter: function() {
                                    setTimeout(function() {
                                        obButton.tipTip('hide');
                                    }, 1500)
                                }
                            }).tipTip('show');
                        }
                    });
                    clipboard.on('error', function(e) {
                        obButton.tipTip({
                            content: 'Не удалось скопировать',
                            activation: 'manual',
                            theme: 'white',
                            afterEnter: function() {
                                setTimeout(function() {
                                    obButton.tipTip('hide');
                                }, 1500)
                            }
                        }).tipTip('show');
                    });
                }
            });
        };
        initCopy();
    }
    //~ копирование логина/пароля демо-пользователя
})