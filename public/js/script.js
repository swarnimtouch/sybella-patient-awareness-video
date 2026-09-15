$(document).ready(function() {

    if ($('#successAlert').length > 0) {
        $('html, body').animate({
            scrollTop: $('#formContainer').offset().top - 30
        }, 600);
    }

    $('#mobileInput').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
    });

    $(".toggle-password").click(function() {
        $(this).toggleClass("fa-eye-slash fa-eye");
        var input = $(this).siblings("input");
        if (input.attr("type") === "password") {
            input.attr("type", "text");
        } else {
            input.attr("type", "password");
        }
    });

    $("#employeeLoginForm").validate({
        rules: {
            employee_code: {
                required: true,
                minlength: 3
            },
            password: {
                required: true,
                minlength: 6
            }
        },
        messages: {
            employee_code: {
                required: "Please enter your employee code",
                minlength: "Employee code must be at least 3 characters"
            },
            password: {
                required: "Please enter your password",
                minlength: "Password must be at least 6 characters"
            }
        },
        errorPlacement: function(error, element) {
            error.insertAfter(element.parent(".icon-input-wrapper"));
        }
    });

    $("#videoForm").validate({
        ignore: [],
        rules: {
            doctor_id: { required: true },
            doctor_name: { required: true, maxlength: 255 },
            speciality: { required: true },
            hospital_name: { required: true },
            hospital_address: { required: true },
            mobile: {
                required: true,
                digits: true,
                minlength: 10,
                maxlength: 10
            },
            language: { required: true },
            photo: { required: true }
        },
        messages: {
            doctor_id: { required: "Please select a doctor" },
            doctor_name: {
                required: "Please enter doctor name",
                maxlength: "Doctor name cannot exceed 255 characters"
            },
            speciality: { required: "Please enter speciality" },
            hospital_name: { required: "Please enter hospital name" },
            hospital_address: { required: "Please enter hospital address" },
            mobile: {
                required: "Please enter mobile number",
                digits: "Only numbers are allowed",
                minlength: "Mobile must be 10 digits",
                maxlength: "Mobile must be 10 digits"
            },
            language: { required: "Please select a language" },
            photo: { required: "Please upload a photo" }
        },
        errorElement: 'div',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback text-start');
            if (element.attr("name") === "photo") {
                $('#photoErrorContainer').html(error);
            } else {
                element.closest('.mb-4').append(error);
            }
        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalid');
            if ($(element).attr("name") === "photo") {
                $(element).closest('.upload-box').addClass('is-invalid-box');
            } else {
                $(element).closest('.input-group-custom').addClass('is-invalid-group');
            }
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass('is-invalid');
            if ($(element).attr("name") === "photo") {
                $(element).closest('.upload-box').removeClass('is-invalid-box');
            } else {
                $(element).closest('.input-group-custom').removeClass('is-invalid-group');
            }
        },
        submitHandler: function(form) {
            $('#videoProcessingOverlay').removeClass('d-none').addClass('d-flex');
            $('body').addClass('video-processing-active');
            $('#submitBtn').addClass('d-none');
            $('#dummyButtons').removeClass('d-none').addClass('d-flex');
            form.submit();
        }
    });

    // Back-forward cache se page restore ho to stale loader hata do.
    window.addEventListener('pageshow', function () {
        $('#videoProcessingOverlay').addClass('d-none').removeClass('d-flex');
        $('body').removeClass('video-processing-active');
    });

    if (typeof window.doctorsData !== 'undefined') {
        $('select[name="doctor_id"]').on('change', function() {
            let selectedId = $(this).val();
            let doc = window.doctorsData.find(d => d.id == selectedId);
            $('#msl').val(doc ? doc.msl_number : '');

            const doctorNameField = $('#doctorNameEditField');
            const doctorNameInput = $('#doctorNameInput');

            if (doc) {
                doctorNameInput.val(doc.name).prop('disabled', false);
                doctorNameField.removeClass('d-none');
            } else {
                doctorNameInput.val('').prop('disabled', true);
                doctorNameField.addClass('d-none');
            }
        });

        if ($('select[name="doctor_id"]').val()) {
            $('select[name="doctor_id"]').trigger('change');
        }
    }

    /* ========== Round Photo Cropper Logic ========== */
    const MAX_PHOTO_BYTES = 2 * 1024 * 1024; // 2MB — server ke max:2048 se match

    let cropperInstance = null;
    const photoInput     = document.getElementById('photoInput');
    const croppedInput   = document.getElementById('croppedPhotoInput');
    const cropImage      = document.getElementById('cropImage');
    const cropModalEl    = document.getElementById('cropModal');
    const cropModal      = new bootstrap.Modal(cropModalEl);
    const previewBox     = document.getElementById('previewContainer');
    const previewImg     = document.getElementById('previewImage');
    const uploadContent  = document.getElementById('uploadContent');
    const cropSaveBtn    = document.getElementById('cropAndSaveBtn');
    const changePhotoBtn = document.getElementById('changePhotoBtn');

    // Jab user file select kare
    photoInput.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        if (!['image/jpeg', 'image/png'].includes(file.type)) {
            alert('Please upload JPG or PNG only.');
            this.value = '';
            return;
        }

        // Original file hi 2MB se bada hai — cropper kholne se pehle turant reject.
        if (file.size > MAX_PHOTO_BYTES) {
            alert('Photo size must be under 2MB. Please choose a smaller photo.');
            this.value = '';
            return;
        }

        const url = URL.createObjectURL(file);
        cropImage.src = url;

        // Modal open hone tak wait karo, phir Cropper init karo
        $(cropModalEl).one('shown.bs.modal', function () {
            if (cropperInstance) cropperInstance.destroy();
            cropperInstance = new Cropper(cropImage, {
                aspectRatio: 1,          // 1:1 square (round crop ke liye)
                viewMode: 1,             // Crop box canvas ke bahar nahi jayega
                autoCropArea: 0.9,       // 90% area default selected
                movable: false,          // Image andar se nahi hilega (Requirement fulfilled)
                zoomable: true,          // Sirf zoom in/out hoga
                zoomOnTouch: true,
                zoomOnWheel: true,
                rotatable: false,
                scalable: false,
                background: false,       // Background grid hide (clean look)
                responsive: true,
                cropBoxMovable: true,    // Crop box (circle) user move kar sake
                cropBoxResizable: true,  // Crop box ka size user badal sake
            });

            // Image load hote hi opacity 1 kar do taaki flash na lage
            cropImage.style.opacity = 1;
        });

        cropModal.show();
    });

    // "Crop & Save" button
    cropSaveBtn.addEventListener('click', function () {
        if (!cropperInstance) return;

        // Square canvas lo Cropper se. Preview box CSS (border-radius:50%) se
        // round hi dikhega, isliye alag se circular canvas banane ki zaroorat
        // nahi — aur JPEG PNG se kaafi chhoti file deta hai (2MB limit ke liye
        // zaroori, kyunki circular PNG aksar 2MB cross kar jaati thi).
        const canvas = cropperInstance.getCroppedCanvas({
            width: 500,
            height: 500,
            minWidth: 256,
            minHeight: 256,
            maxWidth: 1024,
            maxHeight: 1024,
            fillColor: '#fff',
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        if (!canvas) {
            alert('Crop failed. Please try again.');
            return;
        }

        cropSaveBtn.disabled = true;

        // JPEG quality step-down karte hai jab tak file 2MB ke andar na aa jaye.
        function exportUnderLimit(quality) {
            canvas.toBlob(function (blob) {
                if (!blob) {
                    cropSaveBtn.disabled = false;
                    alert('Could not process image. Try another.');
                    return;
                }

                if (blob.size > MAX_PHOTO_BYTES && quality > 0.5) {
                    exportUnderLimit(quality - 0.1);
                    return;
                }

                if (blob.size > MAX_PHOTO_BYTES) {
                    cropSaveBtn.disabled = false;
                    alert('Cropped photo is still larger than 2MB. Please zoom out a little and try again.');
                    return;
                }

                const croppedFile = new File([blob], 'cropped_photo.jpg', { type: 'image/jpeg' });

                // Hidden file input me daalo using DataTransfer
                const dt = new DataTransfer();
                dt.items.add(croppedFile);
                croppedInput.files = dt.files;

                // Preview dikhao aur upload text hide karo
                const reader = new FileReader();
                reader.onload = function (ev) {
                    previewImg.src = ev.target.result;
                    uploadContent.classList.add('d-none');
                    previewBox.classList.remove('d-none');

                    // Validation trigger karo taaki error hata ho
                    $(croppedInput).valid();
                };
                reader.readAsDataURL(croppedFile);

                // Modal band karo & cropper cleanup
                cropModal.hide();
                cropperInstance.destroy();
                cropperInstance = null;
                cropSaveBtn.disabled = false;
            }, 'image/jpeg', quality);
        }

        exportUnderLimit(0.92);
    });

    // "Re-Crop" button - dobara file select karne ka option
    changePhotoBtn.addEventListener('click', function () {
        photoInput.value = '';
        photoInput.click();
    });

    // Agar user modal cancel kare bina crop kiye
    cropModalEl.addEventListener('hidden.bs.modal', function () {
        if (cropperInstance) {
            cropperInstance.destroy();
            cropperInstance = null;
        }
        // Agar cropped image abhi tak set nahi hui, to file input clear karo
        if (croppedInput.files.length === 0) {
            photoInput.value = '';
        }
    });
    /* ========== End: Round Photo Cropper Logic ========== */
});
