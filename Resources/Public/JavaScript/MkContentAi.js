import $ from "jquery";
import Cropper from 'cropperjs';

$(document).ready(function () {
    $("form :submit").not(".noSpinner").click(function () {
        $(this).prop("disabled", true);
        $(this).closest('form').submit();
        $(this).closest('form').html(
            `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading`
        );
    });
    $(".btn:not(form .btn)").not(".noSpinner").click(function () {
        $(this).prop("disabled", true);
        $(this).html(
            `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading`
        );
    });
    $("ul li .dropdown-item").click(function () {
        $(this).prop("disabled", true);
        $(this).closest('div').html(
            `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading`
        );
    });
    const image = document.getElementById('image');
    const submitButton = document.querySelector('input[type="submit"]');
    let dragModeValue = 'none';
    let viewModeValue = 1;
    let cropBoxResizable = true;
    let aspectRatio = 1;
    let scalable = false;
    let autoCropArea = 0;
    let zoomable = true;
    let customWidth = image.width;
    let customHeight = image.height;
    submitButton.disabled = false;

    const cropper = new Cropper(image, {
        aspectRatio: aspectRatio,
        cropBoxResizable: cropBoxResizable,
        dragMode: dragModeValue,
        zoomable: zoomable,
        viewMode: viewModeValue,
        scalable: scalable,
        autoCropArea: autoCropArea,
        crop(event) {
        },
        ready: function () {
            if (document.querySelector('input[name="size"]')) {
                document.querySelector('input[name="size"]').click();
            }
        }
    });

    function updateAspectRatio() {
        customWidth = document.querySelector('input[name="size"]:checked').getAttribute('data-width');
        customHeight = document.querySelector('input[name="size"]:checked').getAttribute('data-height');
        aspectRatio = customWidth / customHeight;
        cropper.setAspectRatio(aspectRatio);
    }

    document.querySelectorAll('input[name="size"]').forEach(function (element) {
        element.addEventListener('change', updateAspectRatio);
    });

    $('.crop-form').on('submit', function (event) {
        event.preventDefault();
        let minHeight;
        let minWidth;

        if (document.querySelector('input[name="size"]:checked')) {
            minWidth = document.querySelector('input[name="size"]:checked').getAttribute('data-width') ?? 256;
            minHeight = document.querySelector('input[name="size"]:checked').getAttribute('data-height') ?? 256;
            minWidth = parseInt(minWidth, 10);
            minHeight = parseInt(minHeight, 10);
        }

        let canvas;
        canvas = cropper.getCroppedCanvas({
            width: minWidth,
            height: minHeight
            }
        );

        let croppedImageSrc = canvas.toDataURL('image/png');
        document.getElementById('croppedImage').src = croppedImageSrc;
        document.getElementById('CroppedBase64').value = croppedImageSrc;
        this.submit();
    });

    document.getElementById('cropSize').addEventListener('change', function (e) {
        if (e.target && e.target.matches('.form-check-input')) {
            const newWidth = e.target.getAttribute('data-width');
            const newHeight = e.target.getAttribute('data-height');

            if (newWidth && newHeight) {
                cropper.setCropBoxData({
                    width: parseInt(newWidth, 10),
                    height: parseInt(newHeight, 10)
                });
            }
        }
    });
});
