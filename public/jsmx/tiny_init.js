/* tiny mce set up */

tinymce.init({
    
    selector:'.useredit',
    toolbar: " undo redo | bold italic | bullist numlist outdent indent blockquote | link image |   emoticons | code ", 
    menubar: false,
    plugins: [
  'advlist autolink link  lists charmap  hr ',
  ' nonbreaking', 'code',
  ' emoticons  textcolor'
]
   
});
