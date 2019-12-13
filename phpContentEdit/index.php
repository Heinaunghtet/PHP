<?php

// Change the following to suit your environment
$basename= pathinfo(basename($_SERVER['SCRIPT_NAME']), PATHINFO_FILENAME);
$html_filename="content.html";   //html file that holds content, and is to be updated
$server_write_hmtl=$_SERVER['PHP_SELF'];  // PHP server page that writes the content
$secret_pw="admin"  ;  //secrete password to allow editable content
// End of configurable code 

error_reporting(E_ALL);
ini_set('display_errors', 'On');

ob_start(); //start buffering the content
session_start(); // start this sesssion
date_default_timezone_set('America/New_York');  //Enable timezone

//Load in the corresponding HTML template file by the same name 
$page_html = file_get_contents($html_filename);  //load in our html content with proper placeholders

$isAuthorized= isset($_REQUEST['pw']) && ( MD5($_REQUEST['pw']))==MD5($secret_pw) ? true: false;

//Are we authorized to change this page, if so lets make teh content editable 
if ( isset($_REQUEST['action']) && $_REQUEST['action']=="edit" && !$isAuthorized )  // 
 {
     echo "<script> alert('Invalid Credentails supplied try again') </script>";
 }
 

 //Ok lets re-write the content for this page
if ( isset($_REQUEST['action']) && $_REQUEST['action']=="write" )  // 
 {
 //     print_r($_REQUEST);
       //$doc = new DOMDocument();

        if (isset($_REQUEST['id']))
            $id= $_REQUEST['id'];

        //Find the ID in the page that was just loaded 
        $matches = array();
        preg_match('#(<div[^>]*id=[\'|"]'.$id.'[\'|"][^>]*>)(.*)</div>#isU', $page_html, $matches);
       
        //extract the contain block of that id
        //var_dump($matches);
        $matched_text =$matches[2];
       // echo "\nMATCHED:\n".$matches[2];  

        //This will become the deplacement div code
        $replacement ="<div class='editable' id=\"".$id."\" {{contentedit}} >".trim($_REQUEST['content'])."</div>";
       // echo "REQUEST:\n".$_REQUEST['content'];  
       // echo "REPLACEMENT:\n".$replacement;  

        //replace the block with the new content, just for the requested <div id=xx> ... </div>
        $new_html= preg_replace('#(<div[^>]*id=[\'|"]'.$id.'[\'|"][^>]*>)(.*)</div>#isU', $replacement , $page_html);
       // echo "NEW:\n".$new_html;

        //write the update page (Entire Page back to disk)
        if (is_writable($html_filename))
        {
        $bytes=file_put_contents($html_filename, $new_html);  //disabled because of bug with regex above
        echo "\n Success wrote $bytes bytes \n";
        }
        else
        die("File cannot not be written to check permissions");

        //return status and exit here.
        die("\n Success  AJAX Function write Content");
    }


// Now let's update the page with various placeholders
$page_html_placeholders = [
   'title' => "Content Editable Page Sample", // title show in the page title
   'server_page' => $server_write_hmtl."?action=edit", //determines what page will handle the server code 
   'action' => isset($_REQUEST['action'])? $_REQUEST['action'] : " Edit",  //action mode
   'contentedit' => isset($_REQUEST['action']) && ( $_REQUEST['action']=='edit' ) && $isAuthorized ? "contenteditable='true'" : ' ', 
   'javascript_content_edit'=>( isset($_REQUEST['action']) &&  $_REQUEST['action']=='edit'  && $isAuthorized )? contentEditJavascript() : ' '
  ];
 
  //save the original page html incase changes aborted
  $disk_page_html=$page_html;
  
   //Now replace the actual patterns/placeholders with actual values
  while($i = current($page_html_placeholders)) {
    $page_html = str_replace('{{'.key($page_html_placeholders).'}}', $i, $page_html );
    next($page_html_placeholders);
    
    }
   
  echo $page_html ;
  ob_end_flush();


  //This funtion is injexted into the web page when valid credentials are supplies
  function contentEditJavascript()
  {
    global $server_write_hmtl;

    $html = <<<HTML
    <script >
    $('.editable').blur(function(){
      var myTxt = $(this).html();
      var myid = $(this).attr('id');
      
      console.log("Updating content: "+myTxt.trim() );
      console.log("content ATTR: "+myid);
  
      $.ajax({
          type: 'post',
          url:  '$server_write_hmtl',
          data: 'content=' +myTxt+"&id="+myid+"&action=write"
      });
  });
  </script>
HTML;

return $html;
  }
?>