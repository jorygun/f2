<?php
namespace digitalmx\flames;

#ini_set('display_errors', 1);


//BEGIN START
	require_once "init.php";

	#require others

	use digitalmx\flames\DocPage;
	use digitalmx as u;
	use digitalmx\flames\Definitions as Defs;

	

	$page = new DocPage;
	$title = "Opportunities"; 
	echo $page->startHead($title, 3);
	echo $page->startBody($title ,2);

//END START
echo <<<EOT
<p>These opportunities have been submitted by FLAMEs members.<br>If you'd like to
post something here, use the link below to enter a New Opportunity, or email the information to the <a href="mailto:editor@amdflames.org" target="_blank">editor</a>.</p>
<p>If you've posted an opportunity here, you can edit or expire it. </p>
<hr>
EOT;



if (isset($_SESSION['login']['user_id'])){
        $userid = $_SESSION['login']['user_id'];
        $user_email = $_SESSION['user_email'];
        $username = $_SESSION['username'];
        $level = $_SESSION['level']; #security level
}
else {$level = 0;}

if (!empty($username) && $username != 'Nobody'){  #user is logged in
    echo "
    <p><b>${username}: Opportunities You Manage</b>
<button onclick=\"window.open('/scripts/opportunity_editor.php?opp=new','opp_edit')\" >Enter New Opportunity</button></p>";


    echo get_opps('user',$username,$pdo);
    echo "<hr>";
}
if ($level > 6){echo get_opps('admin',$username,$pdo);}
else {
 echo "

    ";
    echo get_opps('public',$username,$pdo);
    }

echo "</body></html\n";


#####################################
function get_opps($type,$username='',$pdo){

# type = public or user or admin
if ($type == 'public'){
     $sql = "
            SELECT *
            FROM opportunities
            WHERE expired like '0000-00-00' OR
            expired > NOW()
            ORDER BY  created DESC;";

    }
else if ($type == 'user'){
     $sql = "
            SELECT *
            FROM opportunities
            WHERE owner = '$username'
            AND
            (expired like '0000-00-00' OR
                expired > NOW() - interval 60 day)
            ORDER BY expired DESC,created;";

    }
else if ($type == 'admin'){
     $sql = "
            SELECT *
            FROM opportunities
            WHERE
             (expired like '0000-00-00' OR
                expired > NOW() - interval 90 day)
            ORDER BY expired DESC,created;";
     // $sql = "
//             SELECT *
//             FROM opportunities
//
//             ORDER BY expired,created;";

    }
    

 $result = $pdo->query($sql);
    if ($result->rowCount() >0 ){
       # echo "<table>";
        $status = ' '; $listings = '';
        #$listings .=  "($type listings)<br>";
        $last_status='';
        while ($row = $result->fetch() ){
            $id = $row['id'];
            $editor = "<p><button onclick=\"window.open('/scripts/opportunity_editor.php?opp=${row['id']}','opp_edit')\" class='button'>Edit</button></p>";

            $status = 'Expired';
            $xtime = strtotime($row['expired']);
            $ctime = time();
            if ($row['expired'] == '0000-00-00'){$status = 'Active';}
            elseif ($ctime < $xtime ){$status = 'Active';}
           # echo "id" . $id . " rs=" . $row['expired'] . "; time:" . $ctime . "; xtime: " . $xtime . "<br>\n";
           if ($status != $last_status){
                $listings .= "<h3>$status Opportunities</h3>";
                $last_status = $status;
            }
            $description = thtml($row['description']);
            $listings .=
            "
            <p><b>${row['title']} -  ${row['location']}</b></p>
             Posted By: ${row['owner']}; Contact: <a href=mailto:${row['owner_email']}>${row['owner_email']}</a><br>
             Posted ${row['created']}<br>
             Expires: ${row['expired']}<br>
             Description: $description<br>
             More Info: <a href='${row['link']}' target='_blank'>${row['link']}</a><br>
             <i>Status: $status ($id)</i>
             <br>

             ";
            if (substr($status,0,7)=='Expired'){$listings .= "Expired: ${row['expired']}";}

             #add edit button
             if ($type == 'admin' || ($type == 'user' && $row['owner'] == $username)){
                $listings .= $editor;
            }

            $listings .= "<hr class='gray'>";

        }

    }
    else {$listings = "No current opportunities.";}


    return $listings;
}


