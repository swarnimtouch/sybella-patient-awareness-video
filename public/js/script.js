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
            $('#submitBtn').addClass('d-none');
            $('#dummyButtons').removeClass('d-none').addClass('d-flex');
            form.submit();
        }
    });

    if (typeof window.doctorsData !== 'undefined') {
        $('select[name="doctor_id"]').on('change', function() {
            let selectedId = $(this).val();
            let doc = window.doctorsData.find(d => d.id == selectedId);
            $('#msl').val(doc ? doc.msl_number : '');
        });
    }
    
    $('#photoInput').on('change', function() {
        if (this.files && this.files[0]) {
            $('#uploadText').text(this.files[0].name);
            $(this).closest('.upload-box').removeClass('is-invalid-box');
            $(this).valid(); 
        } else {
            $('#uploadText').text('Tap to Upload Photo');
        }
    });
});