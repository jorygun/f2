<?php
namespace digitalmx\flames;
//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\MyPDO; #if need to get more $pdo

	
   $login->checkLogin(6); 
      #or checkLevel(min) if already logged in.
   
	$page_title = "Create News Item";
	$page_options = ['tiny']; # ['ajax','tiny','votes']
	
   echo $page->startHead($page_title,$page_options); 
 	echo $page ->startBody($page_title);

	
//END START


// script to enter/update a news item
// call with ?id=nnn for edit.
// call with no parameter for new

require_once "news_functions.php";
require_once "asset_functions.php";

$sql_now = sql_now('date');

#set mode as individual user vs admin based on security level.
$mode = ($_SESSION['level']>6)?'admin':'user';
#set up page data for admin level
$page_text='';


//
// $ifields = array(
// 	'title',
// 	'source',
// 	'source_date',
// 	'url',
// 	'type',
// 	'date_published',
// 	'status',
// 	'content',
//  'ed_comment'
// );
// does not include id, , date_entered,date_edited




if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    //if theres an id its an update, otherwise its an insert
	//prepare data
	$pdo = MyPDO::instance();
	
	if ( ! $_POST['title'] ){
	    echo "
	    <p class='red'>No Title Specified for Item</p>
	    <button type='button'  onclick = 'history.back();'>back</button>
	    ";
	    exit;
	}
		$id = $_POST['id']?? 0;
		
     if ($id > 0 && isset($_POST['deleteme']) ){
	    $sql = "DELETE from $itemdb WHERE id = $id;";
	     $pdo->query($sql);
	    exit;
	  }


	$photodata=0; #no data to process
	
	foreach ($ifields as $f){
		if (isset ( $_POST[$f])){$itemdata[$f] = $_POST[$f];}  
	}


    if (empty($itemdata['source_date'])){
        $itemdata['source_date'] = 'Undated';
    }

    if (isset($_POST['use_me']) && $itemdata['status'] != 'T' ){
    	if (isset ($_POST['priority'])){$val = 2;}
    	else {$val =1;}
        $itemdata['use_me']=$val;}
        
    else {
        $itemdata['use_me']=0;
    }

	if (isset($_POST['take_votes']) ){
        $itemdata['take_votes']=1;}
    else {
        $itemdata['take_votes']=0;
    }
   if (isset($_POST['take_comments']) ){
        $itemdata['take_comments']=1;}
    else {
        $itemdata['take_comments']=0;
    }

    // if contributor is empty, default to editor
         if (empty($itemdata['contributor'])){
            $itemdata['contributor'] = 'FLAMES editor';
            $itemdata['contributor_id'] = '';
         }

    // if contributor id not set, look up from name.
    $member = new Member();
        $c = $member->getMemberId($itemdata['contributor']);
        if (!$c){ #user not found
			echo "Error: contributor ${itemdata['contributor']} is not a flames member";
			echo "<button type=button onClick = 'window.history.back();'>Back</button>";
			
           	exit;
        }
       list ( $itemdata['contributor'] ,$itemdata['contributor_id']) = $c;
        


  // title case
    $itemdata['title'] = ucwords($itemdata['title']);

   // add /ed to editorial comment if it's not already commented
    if (    ! empty( $itemdata['ed_comment'])
       # && strrpos( substr($itemdata['ed_comment'],-30),'/') === false
        && ! preg_match('/.*\n--\/[^\n]+\s*$/s',$itemdata['ed_comment'])
        )
        {   $commenter_name = $_SESSION['username'];
            if (in_array($commenter_name, ['FLAMES admin','FLAMES editor'] )){
                $commenter_name = 'editor';
            }
         $itemdata['ed_comment'] .= "\n--/$commenter_name\n";}

     #handle asset first

   /* think about this... if url or file, then new/changed asset.  If asset_id only,
    just conventional post.  Don't worry about asset. DO NOT ALLOW ASSET UPDATE from
    this location... Only a new entry! Use for NEW ASSET only
    */


    if (empty($_POST['asset_id']) && (!empty($_POST['assetlink']) || file_exists($_FILES['linkfile']['tmp_name']))){
        echo "Entering asset.. ";
                $asset_data = array(
                    'id' => '0',
                    'contributor' => $itemdata['contributor'],
                    'contributor_id' => $itemdata['contributor_id'],
                    'source' => $_POST['assetsource'],
                    'vintage' => $_POST['vintage'],
                    'caption' => $_POST['assetcaption'],
                    'url' => $_POST['asseturl'],
                    'title' => $_POST['assettitle'],
                    
                    'link' => $_POST['assetlink']
                );

            $asset_id = post_asset($asset_data);
            echo "new asset: $asset_id";
            $itemdata['asset_id'] = $asset_id;
        }
$itemdata['id'] = $id;
#recho ($itemdata , 'itemdata at ' . __LINE__ . BRNL);


	
	
	if ($id > 0) {
		// update record

		
		$prep = pdoPrep($itemdata,$ifields, $key='id');
		/*
		$prep = pdoPrep($data,$include=[], $key='');
		Example:
		$prep = pdoPrep($post_data,array_keys($model),'id');
		$prep['data'] = array of placeholder=>val, inc fields in include[], but not key
        $prep['update'] = text string for update SET assignment, like email=:email,status=:status
       
        $prep['ifields'] = text like email,status,... for use in update command.
        $prep['ivals'] = text like :email,:status,... for use in update command.
        $prep['key'] = value of field named in $key, used in WHERE clause

    	$sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       	$stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       	$new_id = $pdo->lastInsertId();

   	 	$sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);

        */
        
        
		$sql = "UPDATE `news_items` SET ${prep['update']} WHERE id = '$id';";
		#echo $sql; recho ($prep['data']); #exit;
		$stmt = $pdo->prepare($sql)->execute($prep['data']);

	 }

	else {
		//new record
		if (empty($itemdata['asset_id']) ){
			$itemdata['asset_id'] = 0;
		}
		
		$prep = u\pdoPrep($itemdata,$ifields, $key='id');
		$sql = "INSERT into `news_items` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
		#echo $sql . BRNL; u\echor($prep['data'],'data'); 
       	$stmt = $pdo->prepare($sql)->execute($prep['data']);
       	$id = $pdo->lastInsertId();
		

	}

    $_GET['id'] = $id;

    #echo "<script>window.close()</script>\n";
}


// Else display record




    $alias_keys = Defs::getMemberAliasList();


$istatus = array (
	'N'	=> 'New',
	'R'	=> 'Ready',
	'P'	=> 'Published',
	'E'	=>	'Needs Work',
	'X'	=>	'To Delete',
	'T' => 'Test Article'
);
// display form using data from form_data array

$id=0;
$row = initialize_row();
if (isset ($_GET['id']) && $_GET['id'] != 0) {
        $id = $_GET['id'];
        // retrieve existing record
        $pdo = MyPDO::instance();
        $sql = "SELECT * FROM `news_items` WHERE id = '$id';";

        $row = $pdo->query($sql)->fetch();
        if (empty($row)){echo "Item id $id not found.";exit;}


        if ($asset_id = $row['asset_id']){
            $sql = "select * from `assets` where id = '$asset_id';";
            $asset_data = $pdo->query($sql)->fetch();
            if (empty($asset_data)){
                echo "No such asset : id $asset_id";
                $asset_data['title'] = "No such asset: id $asset_id";
            }
        }
}


	$mystatus = $row['status'];
	$statusoptions = build_options($istatus,$mystatus);






if ($mode == 'user'){
#change istatus to only be ones available to individuals

    $page_text = <<<EOT


    <h4>Personal News Item Editor</h4>
    <p>Use this to create or edit a news article for submitting to the Flame Newsletter.
    </p>

    <p>Remember, your humble <a href='mailto:editor@amdflames.org'>editor</a>
    will review, edit, and choose what goes in, so
    don't worry about getting something wrong.
    </p>

EOT;
     $asset_types = array(
    'Image' ,
    'Multimedia' ,
    'Document' ,
    'Web Page' ,

    );


    $form_prefix = <<<EOT
<div style='background-color:#ccc;border:1px solid black;'>
(Data below cannot be changed)<br>
ID: <input type='text' name = 'id' value='$id' READONLY><br>
Entered: <input type='text' name='date_entered' READONLY value ='${row['date_entered']}'>


<p>status: <input type='text' name='status' READONLY value ='$mystatus' size=4> $istatus[$mystatus] <br></p>
<p>Suggested by: <input type='text' name='contributor' READONLY value='${row['contributor']}'> id: <input type='text' name='contributor_id' READONLY value='${row['contributor_id']}'></p>
</div>
EOT;

    $delete_item = '';
    $mytypes = $ptypes;
}
else { #admin user
     $asset_types = array(
    'Image' ,
    'Cartoon' ,
    'Multimedia' ,
    'Document' ,

    'Web Page' ,
    'Ad',
    'Other'
    );


    $mytypes = $itypes;
    $form_prefix = <<<EOT

<p>ID: <input type='text' name = 'id' value='$id' READONLY><br>
Edited: <input type='text' name='date_edited' READONLY value ='${row['date_edited']}'>

status: <select name='status'>$statusoptions</select>

</p>



EOT;

  $delete_item = "<p class='red'><b>Delete Article</b><input type=checkbox name='deleteme'></p>";
}





#print_r ($itemdata);

#escape quotes etc as needed

foreach (array('title','content','ed_comment','link_title','graphic_caption') as $f){
    if (!empty($row[$f])){
    $form_data[$f] = h($row[$f]);

    }
   # else {echo "$f not changed<br>";}
}
$uchk = ($row['use_me']>0)?'checked':'';
    $pchk = ($row['use_me']>1)?'checked':'';
$take_comments_checked = $row['take_comments']?'checked':'';
$take_votes_checked = $row['take_votes']?'checked':'';
 $typeoptions = build_options($mytypes,$row['type']);

if (!empty($row['asset_id'])){$image = f\get_asset_by_id($row['asset_id']);}



$about_articles = <<<EOT
        <div id='AboutArticles'  style="display:none; border:2px solid green;"  >
        <p>"News Articles" are the stories that will be published in a FLAMEs newsletter.  You create an article with a title and some content and optional link to some website.  You can simultaneously add a graphic to the asset database, and link it to your article.</p>
        <p>All articles are reviewed by the editor prior to being published, so if you make a mistake, don't worry; just let the editor know what you want.
        </p>
        <p><b>Create Your Article Content</b></p>
        <table>
        <tr><td>Title</td><td><i> The Item Title is the headline on the news article</i></td></tr>
        <tr><td>Topic</td><td><i> The Topic determines what section of the newsletter the article will be in.</i></td></tr>
        <tr><td>Source (optional)</td><td><i>If this is from some other publication or source, enter the publication or event or other source and its date. (Date is unformatted text and just for information, so it doesn't have to be in any particular format.)</i></td></tr>
        <tr><td>link title and url (optional)</td><td><i>If there is a link to another site, enter its title and url here.  It will show as a link below your article. If you leave out a title,
it will be shown as "Read More...". Link URL must start with 'http://' or 'https://'</i></td></tr>
        <tr><td>Content</td><td><i>The content of the article, typically a few lines from a magazine article, or whatever you want to say. Content should normally be filled in, but you CAN publish an article without any content, but just title and a graphic. <br> Carriage returns are converted to line breaks. URLs are linked IF they are preceeded by a space; otherwise they display whatever html you type in.</i></td></tr>
        <tr><td>Comment</td><td><i>Your comment on the story; appears in green below the story</i></td></tr>
        <tr><td colspan=2><hr /><b>Adding a Graphic, File, or other Asset</b>
        <br>
            Assets are photos, movies or pdf files catalogued in the searchable "Asset" database on the site.  One or more assets can displayed along with your article. <br>
            Assets are displayed as a "thumbnail" in a 200px wide box to the left of your article text.  Clicking on the thumbnail will bring up a full size display of the asset and the url if someone wants to download it.
            <br><br>
            Every asset has an id number. Assets can be a file stored on the
            site, or can just be a pointer to some url somewhere else.  You add a graphic that's already on the site by referencing its existing ID, or you can create a new asset by uploading a file or a url to a file on the web.<br></td></tr>

            <tr><td colspan='2'><b>Create New Asset</b></td></tr>
            <tr><td></td><td><i>If you have a new graphic to display with your article, you can enter it here.
             It will be assigned an ID number, entered into the Asset catalog, and displayed with your article.
            <tr><td>Asset Type</td><td><i>
                Assets can be images, documents (e.g., PDF), multimedia (mp3, mp4), or web pages (urls to some other place.)
            </i></td></tr>

            <tr><td>Title</td><td><i>
                Short title.  Searched, but not usually displayed.
            </i></td></tr>

            <tr><td>Source and Year</td><td><i>
                Source is basically attribution, like magazine or photographer.
                Year is called "vintage" sometimes.  It is the best guess to the
                year the graphic was created.  You can search for assets by year.
            </i></td></tr>

            <tr><td>Caption</td><td><i>
               Usually displayed under the graphic.  This field is searched, so
               be sure to include the names of people in the photo.
            </i></td></tr>

<tr><td>Thumb Source</td><td><i>The "thumb source" is the file used to create
        the image used inthe asset thumbnail.  Often, this is also the file the
        thumbnail should point to.  You can either specify a url or, most
        commonly, just upload a file</i></td></tr>
<tr><td>Choose a File</td><td><i>
                Press this to open a file dialog box on your computer, from which you can select a file to upload.  Try to keep file
                size minimal (say below 2MB) but larger files and videos can be
                uploaded as well.
            </i></td></tr>

<tr><td>URL</td><td><i>
               You can simply point to a url on another site (hopefully it is a
               reasonably permanent url). If it's a graphic, then a local thumbnail will be created too. URLs to files on amdflames.org should start with a /.  External files should start with "http://".
            </i></td></tr>
<tr><td>Link Thumb Elsewhere</td><td><i>If you want the asset to point to
    something other than the image used to create the thumbnail, then enter
    that url here.  Use this when the asset is not simply the image used for
    the thumbnail, like a video or audio file. </i></td></tr>

 <tr><td><b>Or use existing asset(s)</b></td></tr>
 <tr><td></td>Asset IDs<td><i>
            If the graphic you want is already in the asset database, enter its id number here. You can search assets here: <button type='button' onclick="window.open('/scripts/assets.php' ,'assets','width=1100,left=160');">Search</button>. You can add multiple assets. They will line up left to right. </i>
            </td></tr>
        </table>
        <button type='button' style='background:#cfc' name='ShowAbout' onclick="showDiv('AboutArticles')">Close Help</button>
        </div>
EOT;

$required_text = "<span class='red'>(required)</span>";
$create_edit = ($id==0)?'Create New':'Edit';

echo <<< EOT
<div>
<h3>$create_edit Article with Optional Graphic ($mode)</h3>



<form  method="POST" enctype="multipart/form-data" onsubmit="return check_form(['title','topic']);">

$form_prefix

<hr>
<p><h4>Article Title and Content</h4> <button type='button' style='background:#cfc' name='ShowAbout' onclick="showDiv('AboutArticles')">Help</button></p>
$about_articles
<table>

<tr><td width='160'>Topic: $required_text</td><td><select name='type' id='topic'> $typeoptions</select></td></tr>


<tr><td >Item Title $required_text</td><td><input type='text' size='60' name='title' id='title' value="${row['title']}"></td></tr>
EOT;
if ($mode !== 'user'){echo <<<EOT
<tr><td>Contributed by: </td><td><input type='text' name='contributor' value='${row['contributor']}' onfocus="form.contributor_id.value='';">

id: <input type='text' name='contributor_id' id='contributor_id' value='${row['contributor_id']}' size='6'><br>
(Aliases: $alias_keys.)

</td></tr>

EOT;
}
echo <<<EOT

<tr><td >Source</td><td><input type='text' name='source' value="${row['source']}" size="30"> date: <input type='text' name='source_date' value = "${row['source_date']}" size="15"></td></tr>

<tr><td>url for more info</td><td><input type='text' name='url' value = "${row['url']}" size="60"></td></tr>


<tr><td >title for above link</td><td><input type='text' size='60' name='link_title' value="${row['link_title']}"></td></tr>


<tr><td style="vertical-align:top;">Content</td><td><textarea cols=60 rows=10 name='content' class='useredit' >${row['content']}</textarea></td><tr>

<tr><td>Comment</td><td><textarea cols=60 rows=3 name='ed_comment'>${row['ed_comment']}</textarea></td><tr>

<tr><td colspan='2'>Allow Comments? <input type='checkbox' value='1' name='take_comments' $take_comments_checked> &bull;
Allow Votes? <input type='checkbox' value='1' name='take_votes' $take_votes_checked></td></tr>

<tr><td colspan='2'><h4>Display Existing Assets With Your Article...</h4></tr>
EOT;



echo <<<EOF
<tr><td>Specify existing asset IDs:</td>
    <td style='border:1px solid green'>First Asset ID: <input type=text name='asset_id' size='6' value='${row['asset_id']}'> Additional asset IDs <input type=text name='asset_list' value='${row['asset_list']}'>

<button type='button' onclick="window.open('/scripts/assets.php' ,'assets','width=1100,left=160');">Search Assets</button></td></tr>
<tr><td valign='top' colspan='2'><h4>OR Create A New Asset Here. </h4></td></tr>
EOF;
echo new_asset_form($row['asset_id'],$asset_types);
if ($mode == 'admin'){
    echo "<tr> <td colspan='2'>
    Queue for next <input type='checkbox' name='use_me[]' value='$id' $uchk>
    priority <input type='checkbox' name='priority[]' value='$id' $pchk>
    </td></tr>\n";
}
echo <<<EOF
</table><input type='submit' value='Submit Story and Asset' style='background:#CFC;'>
$delete_item
</form></div>


</body></html>
EOF;




###############
function new_asset_form ($asset_id,$asset_types) {
    $required_text = "<span class='red'>(required)</span>";


    if ($asset_id > 0){
        $image = f\get_asset_by_id($asset_id);
        $button = <<<EOT
         <button type='button' onClick = "window.open('/scripts/asset_edit.php?id=$asset_id','asset_edit')" >Edit Asset $asset_id</button>
EOT;
        $form = <<<EOT
       <tr><td colspan='2'> <hr></td></tr>
       <tr><td style='vertical-align:top'> $button
          </td><td>$image</td></tr>
EOT;
    }

    else { #new asset
     global $asset_tags;
     $tag_options = buildCheckBoxSet('tags',$asset_tags,'',3);
    $assetoptions = build_options($asset_types,'Image');
    $form = <<<EOT
    <tr><td valign='top'></td><td>
    <table style='border:1px solid green;'>


<tr><td>Asset Title $required_text</td><td><input type='text' size='60' name='assettitle' ></td></tr>
<tr><td>Attribution</td><td><input type='text' name='assetsource'  size="40"> </td></tr>
<tr><td>Year (approx):</td><td> <input type='text' name='vintage'  size="6"> </td></tr>
<tr><td>Caption (include names of people)</td><td><textarea  name='assetcaption' rows=5 cols=60></textarea></td></tr>

<tr><td colspan='2'>Enter EITHER a url to the asset  OR select a file to upload</td></tr>

<tr><td colspan='2'>Link asset to this file or url </td></tr>
<tr><td></td><td>
Upload file <input type="file" name="linkfile" > <br>
    or URL: <input type='text' name='assetlink' }' size=80>
    </td></tr>


<tr><td colspan='2'>Upload a file to create the thumbnail from if not the link. <br>
(thumbs automatically created if link file is a jpeg, pdf or youtube video.)</td></tr>
<tr><td></td><td>-- Upload thumb/source file:
    <input type="file" name="upfile" id="photo"><br>
--- or designate source URL: <input type='text' name='asseturl'  size='80'>
     </td></tr>



<tr><td>Tags</td><td>$tag_options</td></tr>
    </table>
    </td></tr>
EOT;
    }
    return $form;
}

function initialize_row () {

    $form = array (
        'date_entered'	=>	sql_now('date'),

        'date_edited' => sql_now('date'),
        'use_me' => '',
        'type' => '',
        'title'=>'',
        'source'=>'',
        'url'=>'',
        'link_title' => '',
        'ed_comment' => '',
			'asset_id' => '',
			'asset_list'=>'',
			
        'contributor'	=>	$_SESSION['login']['username'],
        'contributor_id'	=>	$_SESSION['login']['user_id'],
        'status'	=>	'N',
        'id'    => 0,
        'source_date' => date('d M, Y'),
        'content'	=>	"",
        'take_comments'	=>	true,
        'take_votes' => true,

    );

    if ($_SESSION['login']['username'] == 'FLAMES admin'){

       $form [ 'contributor']	=	'FLAMES editor';
        $form ['contributor_id']	=	'';
    }
    return $form;
}


