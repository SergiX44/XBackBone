var app = {
    run: function () {
        $('[data-toggle="tooltip"]').tooltip();

        $('.user-delete').click(app.modalDelete);
        $('.media-delete').click(app.mediaDelete);
        $('.publish-toggle').click(app.publishToggle);
        $('.refresh-token').click(app.refreshToken);

        $('.alert').fadeTo(2000, 500).slideUp(500, function () {
            $('.alert').slideUp(500);
        });

        new ClipboardJS('.btn-clipboard');

        console.log('Application is ready.');
    },
    modalDelete: function () {
        $('#modalDelete-link').attr('href', $(this).data('link'));
        $('#modalDelete').modal('show');
    },
    publishToggle: function () {
        var id = $(this).data('id');
        var $callerButton = $(this);
        if ($(this).data('published')) {
            $.post(window.AppConfig.base_url + '/upload/' + id + '/unpublish', function () {
                $callerButton
                    .data('published', false)
                    .tooltip('dispose')
                    .attr('title', 'Publish')
                    .tooltip()
                    .removeClass('btn-outline-warning')
                    .addClass('btn-outline-info')
                    .html('<i class="fas fa-check-circle"></i>');
                $('#published_' + id).html('<span class="badge badge-danger"><i class="fas fa-times"></i></span>');
            });
        } else {
            $.post(window.AppConfig.base_url + '/upload/' + id + '/publish', function () {
                $callerButton
                    .data('published', true)
                    .tooltip('dispose')
                    .attr('title', 'Unpublish')
                    .tooltip()
                    .removeClass('btn-outline-info')
                    .addClass('btn-outline-warning')
                    .html('<i class="fas fa-times-circle"></i>');
                $('#published_' + id).html('<span class="badge badge-success"><i class="fas fa-check"></i></span>');
            });
        }
    },
    mediaDelete: function () {
        var id = $(this).data('id');
        var $callerButton = $(this);
        $.post(window.AppConfig.base_url + '/upload/' + id + '/delete', function () {
            $callerButton.tooltip('dispose');
            $('#media_' + id).fadeOut(200, function () {
                $(this).remove();
            });
        });
    },
    refreshToken: function () {
        var id = $(this).data('id');
        $.post(window.AppConfig.base_url + '/user/' + id + '/refreshToken', function (data) {
            $('#token').val(data);
        });
    }
};

$(document).ready(app.run);
