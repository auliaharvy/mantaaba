<script src="{{ asset('plugins/custom/tinymce/tinymce.bundle.js') }}"></script>
<script>
    tinymce.init({
        selector: '{{ $selector ?? '#body' }}',
        height: 500,
        menubar: 'edit view insert format table',
        plugins: ['advlist autolink lists link image charmap preview anchor', 'searchreplace visualblocks code fullscreen', 'media table paste wordcount'],
        toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image table | code',
        content_style: "@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap'); body { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 15px }"
    });
</script>
