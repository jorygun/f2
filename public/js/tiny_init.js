/* tiny mce set up */

tinymce.init({
    
    selector:'.useredit',
    toolbar: " undo redo | bold italic | bullist numlist outdent indent blockquote | link image |   emoticons | paste | code ", 
    menubar: false,
    width: 600,
    relative_urls:false,
    content_css : '/css/news4.css' , 
//    paste_enable_default_filters: false,
//    paste_word_valid_elements: "b,strong,i,em,a,ul,li",
    plugins: [
  'advlist autolink link  lists charmap  hr ',
  ' nonbreaking', 'code', 'paste',
  ' emoticons'
	]
   
});

 
