/* tiny mce set up */

tinymce.init({
    
    selector:'.useredit',
    toolbar: " undo redo | bold italic | bullist numlist outdent indent blockquote | link image |   emoticons | code ", 
    menubar: false,
    width: 600,
    content_css : '/css/news3.css' , 
    plugins: [
  'advlist autolink link  lists charmap  hr ',
  ' nonbreaking', 'code',
  ' emoticons  textcolor'
]
   
});

 
