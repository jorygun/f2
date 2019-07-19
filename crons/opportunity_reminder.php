#!/usr/local/bin/php
<?php
#$test = 'jorygun@gmail.com'; #uncomment to send all email to this address.
$script = basename(__FILE__);
include './cron-ini.php';
if (! @defined ('INIT')) { die ("$script halting. Init did not succeed \n");}



//END START
ini_set('display_errors', 1);


/*
    * this script is run by cron to send reminder notices
    * to any users that have job postings listed.
*/



echo "Active Opportunity Reminders
---------------------------------------------
";

    $opp_list =  get_opps($pdo);
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


function get_opps($pdo){
# type = public or user or admin

  
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

