<?php

// Set to FALSE if you are using "Unique link for each account" tracking method in ToplistX
$embedded = TRUE;

// Change this to the "Tracking URL" setting from ToplistX
$new_in_url = 'http://www.yoursite.com/index.shtml';



//////////////////////////////////////////////////////////////////////////
//                        DONE EDITING THIS FILE                        //
//////////////////////////////////////////////////////////////////////////

if( $embedded )
{
    header("Location: $new_in_url");    
}
else
{
    header("Location: $new_in_url?id={$_GET['id']}");   
}

?>