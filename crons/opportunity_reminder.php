#!/usr/local/bin/php
<?php
#$test = 'jorygun@gmail.com'; #uncomment to send all email to this address.

//BEIN START
// set ini because this file runs from cron so doesn't know .user.ini or shell
$sitedir = dirname(__DIR__);
require_once "$sitedir/config/boot.php";


//END START
#ini_set('display_errors', 1);
#ini_set('error_reporting',E_ALL);

/*
    * this script is run by cron to send reminder notices
    * to any users that have job postings listed.
*/



echo "Active Opportunity Reminders
---------------------------------------------
";

    $opp_list =  get_opps();
    $last_owner = '';
    $listing_text = '';
   $intro = <<<eot
This is a reminder that you have the following
job opportunities listed as open opportunities on
the AMD Flames site.  If you wish to cancel these,
log into the site, click Opportunities, then the Edit
button, then change the expiration date.

If you have problems, contact the site admin
at admin@amdflames.org.

Your Listings:
   Created    Expires    Title
   ---------------------------------------------------

eot;

    foreach ($opp_list as $listing){
        list ($title,$owner,$owner_email,$created,$expired) = $listing;

        if ($last_owner != '' && $owner != $last_owner){
            sendit ($last_owner,$last_owner_email,$intro,$listing_text );
            $listing_text = '';
        }
        $listing_text .= "   $created $expired $title\r\n";
        $last_owner = $owner;
        $last_owner_email = $owner_email;


        echo "$title,$owner,$owner_email,$created\n";

    }
    sendit ($last_owner,$last_owner_email,$intro,$listing_text );



#####################################
function sendit ($to,$email,$intro,$listings,$test=''){
  // if test mode, send al emails to one test address.
  if (!empty($test)){$email = $test;}

 $message = "To: $to $email\r\n\n"
            . $intro
            . $listings
            . "   ---------------------------------------------------\r\n";

            mail($email,'Open Listings on AMD Flames',$message,"From: AMD Flames Admin <admin@amdflames.org>\r\ncc:admin@amdflames.org\r\n");
}


function get_opps(){
# type = public or user or admin

  $pdo = MyPDO::instance();

     $sql = "
            SELECT title,owner,owner_email,created,expired
            FROM opportunities
            WHERE expired like '0000-00-00' OR
            expired > NOW()
            ORDER BY  owner;";

    $result =$pdo -> query($sql);
    $listings = array();
    if (!result){return $listings;}

    while ($row = $result->fetch()){
        $listings[] = array(
            $row['title'],
            $row['owner'],
            $row['owner_email'],
            $row['created'],
            $row['expired']
            );
            #echo "${row['title']}<br>";
    }

    return $listings;
}

