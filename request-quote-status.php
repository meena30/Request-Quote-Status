<?php
/*
  Plugin Name: Request Quote Status 
  Description: To display the request quote submitted details 
  Version: 1.0
  Author: Meena
*/
?>
<?php

/*register our custom css here */
	add_action('admin_init', 'quote_plugin_admin_init');
	function quote_plugin_admin_init() {
		wp_register_style('cus-quote', '/wp-content/plugins/request-quote-status/css/cus-quote.css');
		wp_enqueue_style('cus-quote');
	}
/*register the admin menu */	
add_action("admin_menu", "add_admin_menu_sidebar");
function add_admin_menu_sidebar(){

add_menu_page("Request Quote Details","Request Quote Details","manage_options","request_quote_details","request_quote_details", "dashicons-edit", 100);
add_submenu_page("request_quote_details","filter menu","","manage_options","filtermenu","filter_details");

}

// ** Filter_details function start **//
function filter_details(){
	global $wpdb;
	global $paged;
 	$filter = $_GET['status'];
	if(isset($_POST['stfilter'])){
	 	$data = $_POST['status'];
		$search_str = str_replace(' ', '+', $data); // Make a single space a '+'
		$filurl = "admin.php?page=filtermenu&status=".$search_str;
		wp_redirect( admin_url( $filurl) );
		exit;
	}
	
	?>
	<form action="" method="post" class="filter_form"> 
		<select class="form-control filter_option" name="status">                           
            <option <?php if ($_GET['status'] == "all" ) echo 'selected' ; ?> value="all">All Status</option>
            <option <?php if ($_GET['status'] == "New" ) echo 'selected' ; ?> value="New">New</option>
            <option <?php if ($_GET['status'] == "Quote Submitted" ) echo 'selected' ; ?> value="Quote Submitted">Quote Submitted</option>
            <option <?php if ($_GET['status'] == "Quote Accepted" ) echo 'selected' ; ?> value="Quote Accepted">Quote Accepted</option>
            <option <?php if ($_GET['status'] == "Quote Declined" ) echo 'selected' ; ?> value="Quote Declined">Quote Declined</option>
       	</select>
  			<input type="submit" name="stfilter" class="filtebtn" value="Filter">
	</form>
	<?php
		$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
		$lim = 10; // number of rows in page
		$offset1 = ( $pagenum - 1 ) * $lim;
		
		if($_GET['status'] == "all"){
			$total1 = $wpdb->get_var( "SELECT COUNT(`id`) FROM nel_request_quote" );
		}
		else{ $total1 = $wpdb->get_var( "SELECT COUNT(`id`) FROM nel_request_quote WHERE status ='$filter'" ); }

		$page_count = ceil( $total1 / $lim );

		$query = "SELECT id, user_name, user_email, product_id, product_name, status, request_date  FROM nel_request_quote";

		if($_GET['status'] == "all"){ $query .= " WHERE status IS NOT NULL"; }
		else{ $query .= " WHERE status ='$filter'"; }
	 	$query .= " ORDER BY `id` DESC LIMIT $offset1, $lim";
	 	$filter_rec = $wpdb->get_results($query);
	
 if($total1 > 0)
	{
		echo "<table id='req_detail'><thead><tr><th>Name</th><th>Email ID</th><th>Product Name</th><th>Product ID</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody>";

	foreach ($filter_rec as $fkey => $fvalue) {
		
		  $filter_id 		= $fvalue->id;
		  $filter_name 		= $fvalue->user_name;
		  $filter_email 	    = $fvalue->user_email;
		  $filter_pro_id 	= $fvalue->product_id;
		  $filter_pro_name   = $fvalue->product_name;
		  $filter_status 	= $fvalue->status;
		  $filter_date 		= $fvalue->request_date;

			echo "<tr>";
			echo '<td>'.$filter_name.'</td><td>'.$filter_email.'</td><td>'.$filter_pro_name.'</td><td>'.$filter_pro_id.'</td><td>'.$filter_date.'</td><td>'.$filter_status.'</td>';
			echo '<td>';
			$filter_pro_status = get_post_meta( $filter_pro_id, '_stock_status', true );
			if($filter_status == "New" && $filter_pro_status != 'outofstock'){
			echo '<a href="admin.php?page=request_quote_details&upt='.$filter_id.'">Reply Quote</a><br>';
			}
			echo '</td>';
			echo "</tr>";
		}//end foreach
			echo "</tbody></table>";
		$navi_links = paginate_links( array(
				    'base' => add_query_arg( 'pagenum', '%#%' ),
				    'format' => '',
				    'prev_text' => __( '&laquo;', 'aag' ),
				    'next_text' => __( '&raquo;', 'aag' ),
				    'total' => $page_count,
				    'current' => $pagenum
				) );

			if ( $navi_links ) {
			    echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . 
			$navi_links . '</div></div>';
			}	
	}
}
// ** Filter_details function end **//

/* Display all the quote details as table format- Start */
function request_quote_details() {
	global $wpdb;
	global $paged;
	$table_name = $wpdb->prefix . "request_quote";
	if(isset($_POST["uptsubmit"])) {
		 $req_err = 0;
		 $status_error  = '';
		 $upt_name 	    = $_POST["uptname"];
		 $upt_email 	= $_POST["uptemail"];
		 $req_id 		= $_POST["req_id"];
		 $price 		= $_POST["req_price"];
		 $pct_name      = $_POST["uptpro_name"];
		 $comments      = $_POST["comment"];
		 $quo_status 	= "Quote Submitted";
		 $cus_status 	= "Quote Received";

		 if(!preg_match('/^[0-9]{1,6}+(\.[0-9]{1,2})?$/', $price)){
		 	 $req_err = 1;
		 	 $status_error = 'Price field accept maximum of 6 digits & 2 decimal points only';
		 }
		 $admin_status = $wpdb->get_var("SELECT status FROM $table_name WHERE id='$req_id'");
		 if($admin_status == "Quote Submitted"){
		 	$status_error = "Already you have submitted quote for this product.";
		 }
		 elseif($req_err == 0){

		 $wpdb->query("UPDATE $table_name SET price='$price', description='$comments', status='$quo_status', customer_status='$cus_status' WHERE id='$req_id'");
		 
			$new_price='SEK '.$price;
			$admin_email  = 'meena@yopmail.com'; //admin mail id
 		    $headers      = 'From: '.$admin_email . "\r\n";
 		    $headers     .= "MIME-Version: 1.0\r\n";
            $headers     .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
            $subject      = "Northern Lights- Request Quote Price Details";
            $quotemsg     = "<p style='margin-left:20px;'><img style='display: block;margin: 30px auto;' src='http://northearn.wpengine.com/wp-content/uploads/2018/12/northernlight-logo.jpg'></p>";
            $quotemsg     .= "<p style='margin-left:20px;'>Hi $upt_name</p>";
            $quotemsg    .= "<p style='margin-left:20px;'>Kindly find your request quote price details:</p>";
            $quotemsg    .= "<p style='margin-left:20px;'>Product Name : $pct_name</p>";
            $quotemsg    .= "<p style='margin-left:20px;'>Price : $new_price</p>";
            $quotemsg    .= "<p style='margin-left:20px;'>Product Description : $comments</p>";
            $quotemsg    .= "<p style='margin-left:20px;'>Thanks</p><br>";
            
            wp_mail($upt_email, $subject, $quotemsg, $headers ); /* client mail content */
            wp_redirect( admin_url("admin.php?page=request_quote_details") );
        }
	}

	if(!isset($_GET["upt"]) || isset($_POST["filter"]))
	{
		$filter = $_REQUEST['status'];
		if($filter){
			$string = str_replace(' ', '+', $filter); // Make a single space a '+'
			$url = "admin.php?page=filtermenu&status=".$string;
			wp_redirect( admin_url( $url) );
  			exit;
		}
		$reply_succ = $_GET['success'];
		if($reply_succ){ ?>
                <div id ="reply_success_hide" class="reply_success_hide">Quote was submitted successfully.</div> 
            <?php }
		?>
		
		<form action="" method="post" class="filter_form">
			<select class="form-control filter_option" name="status">                           
                <option value="all">All Status</option>
                <option value="New">New</option>
                <option value="Quote Submitted">Quote Submitted</option>
                <option value="Quote Accepted">Quote Accepted</option>
                <option value="Quote Declined">Quote Declined</option>
           	</select>
  			<input type="submit" name="filter" class="filtebtn" value="Filter">
		</form>

		<?php
		$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
		$limit = 15; // number of rows in page
		$offset = ( $pagenum - 1 ) * $limit;
		$total = $wpdb->get_var( "SELECT COUNT(`id`) FROM ".$table_name."" );
		$num_of_pages = ceil( $total / $limit );
		$quote_rec = $wpdb->get_results("SELECT id, user_name, user_email, product_id, product_name, status, request_date  FROM ".$table_name." ORDER BY `id` DESC LIMIT $offset, $limit ");
	
	if($total > 0)
	{
		echo "<table id='req_detail'><thead><tr><th>Name</th><th>Email ID</th><th>Product Name</th><th>Product ID</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody>";
		foreach ($quote_rec as $key => $value) {
			 $rec_id 		= $value->id;
			 $cu_name 		= $value->user_name;
			 $cu_email 	    = $value->user_email;
			 $cu_pro_id 	= $value->product_id;
			 $cu_pro_name   = $value->product_name;
			 $cu_status 	= $value->status;
			 $cu_date 		= $value->request_date;
		echo "<tr>";
		echo '<td>'.$cu_name.'</td><td>'.$cu_email.'</td><td>'.$cu_pro_name.'</td><td>'.$cu_pro_id.'</td><td>'.$cu_date.'</td><td>'.$cu_status.'</td>'; 
		echo '<td>';
		$product_status = get_post_meta( $cu_pro_id, '_stock_status', true );
			if($cu_status == "New" && $product_status != 'outofstock'){
			echo '<a href="admin.php?page=request_quote_details&upt='.$rec_id.'">Reply Quote</a><br>';
			}
			echo '</td>';
		echo "</tr>";
		}
		echo "</tbody></table>";
		$page_links = paginate_links( array(
			    'base' => add_query_arg( 'pagenum', '%#%' ),
			    'format' => '',
			    'prev_text' => __( '&laquo;', 'aag' ),
			    'next_text' => __( '&raquo;', 'aag' ),
			    'total' => $num_of_pages,
			    'current' => $pagenum
			) );

			if ( $page_links ) {
			    echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . 
			$page_links . '</div></div>';
			}
	}
	else{
		echo $quote_errors = '<div class="quote_error"> No Records found</div>';
	}
}	

	/* edit and update code */
	else{
	if(isset($_GET["upt"])) {
		 $upt_id = $_GET["upt"];
		 $result = $wpdb->get_results("SELECT * FROM ".$table_name." WHERE id='$upt_id'");
		
				foreach($result as $print) {
					$name 			= $print->user_name;
					$email 			= $print->user_email;
					$pro_id 		= $print->product_id;
			 		$pro_name   	= $print->product_name;
			 		$status 		= $print->status;
			 		$date 		    = $print->request_date;
			}
			if($quote_succ) { ?>
            <span class="quote_success"><?php echo trim($quote_succ, '"'); ?></span> <!--error message -->
        <?php }else{?>
        		<span class="status_error"><?php echo trim($status_error, '"'); ?></span> <!--error message -->
        	<?php }
			$form = '';
			$form .='<div class="reply_form_section">';
			$form .= '<h2 class="rply-form">Reply to Customer Quotation</h2>';
			$form .= '<form action="" class="quote-reply-form container" method="post">';
			$form .= '<div class="form-group"><label for=""> Name </label><input class="form-control" type="text" name="uptname" id="uptname" placeholder=""  value="'.$name.'" readonly /></div>';
			$form .= '<div class="form-group"><label for=""> Email </label><input class="form-control" type="email" name="uptemail" id="uptemail" placeholder=""  value="'.$email.'" readonly /></div>';
			$form .= '<div class="form-group"><label for=""> Product Name </label><input class="form-control" type="text" name="uptpro_name" id="uptpro_name" placeholder=""  value="'.$pro_name.'" readonly /></div>';
			$form .= '<div class="form-group"><label for=""> Price</label><input class="form-control" type="text" name="req_price" id="req_price" placeholder="Enter your price"  value="'.$_POST["req_price"].'" /></div>';
			$form .= '<div class="form-group"><label for="">Description</label><textarea name="comment" rows="4" cols="50" placeholder="Enter your product description"></textarea></div>';
			$form .= '<div class="form-group"><input class="form-control" type="hidden" name="req_id" id="req_id" placeholder=""  value="'.$upt_id.'" /></div>';
			$form .= '<input type="submit" id="uptsubmit" name="uptsubmit" value="Submit">';
			$form .= '</form>';
			$form .= '</div>';
			echo $form; 
	 	}
	}//else end

}
/* quote details - END */

?>

