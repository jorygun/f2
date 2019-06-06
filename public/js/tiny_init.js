/* tiny mce set up */



tinymce.init({
    
    selector:'.useredit',
    theme: 'silver',
    width: 500,
    height:200,
    toolbar: " undo redo | bold italic | bullist numlist outdent indent blockquote | link image |   emoticons | code ", 
    menubar: false,
    plugins: [
  'advlist autolink link  lists charmap  hr ',
  ' nonbreaking', 'code',
  ' emoticons '
],
	content_css: "/css/content.css",
   
});

