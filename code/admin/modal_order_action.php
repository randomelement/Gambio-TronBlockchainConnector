<?php
/* --------------------------------------------------------------
   Tron Europe Dev Team
   Filename: modal_order_action.php 
   
   15.09.2018 - Init Version
   
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]

   IMPORTANT! THIS FILE IS DEPRECATED AND WILL BE REPLACED IN THE FUTURE. 
   MODIFY IT ONLY FOR FIXES. DO NOT APPEND IT WITH NEW FEATURES, USE THE
   NEW GX-ENGINE LIBRARIES INSTEAD.
   --------------------------------------------------------------

   based on:
   (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
   (c) 2002-2003 osCommerce(orders_status.php,v 1.19 2003/02/06); www.oscommerce.com
   (c) 2003	 nextcommerce (orders_status.php,v 1.9 2003/08/18); www.nextcommerce.org
   (c) 2003 XT-Commerce - community made shopping http://www.xt-commerce.com ($Id: orders_status.php 1125 2005-07-28 09:59:44Z novalis $)

   Released under the GNU General Public License
   --------------------------------------------------------------*/
   
    // include external library
	include 'tron-extension/php/inc/global_lib.php';
	include 'tron-extension/php/inc/global_settings.php';
	require_once('includes/application_top.php');
	
    // extract orderid
	$orderid = $_POST['data'];
	
    // extract transaction hash
	$action = $_POST['action'];
	
	// extract transaction hash
	$hash = $_POST['hash'];

	//create dbconnection
	$dbconn = dbconnect($dbname[0]);
	
	// generate default error
	$default_error = '<option value="-1">No Data</option>';
	
	// fetch shop address
	$shop_wallet_address = getdbparameter('shopaddress');
	
	// check dbconnection
	if (dbconncheck($dbconn)) {
		
		// action -> search	
		if ($action == 'search'){
			// generate sql query
			$dbquery = "SELECT orders_id,customers_name FROM orders WHERE orders_id like '%".$orderid."%' LIMIT 10";
			
			// send db query
			$result=dbquery($dbquery);
			
			// check db result
			if (mysqli_num_rows($result) > 0) {
				
				// generate option values
				while($data = mysqli_fetch_assoc($result)) {
					echo '<option value="'.$data['orders_id'].'">'.$data['orders_id'].' ('.$data['customers_name'].')</option>';
				}			
			}
			// error message
			else echo $default_error;
		}
		// action -> change
		else if ($action == 'change'){
			if ($orderid<>'-1'){
							   
				// set transactionstate -> Order assigned
				$db_transaction_data['transaction_state'] = 'TRX_TRANSACTIONTATE_2';
				
				// orderid and purpose of use match
				$db_transaction_data['order_assignment'] = 0;
				
				// set orderid 
				$db_transaction_data['trans_orderid'] = $orderid;
				
				// check orderinformations -> send sql query to database
				$gambio_order_check = mysqli_query($dbconn, system_gen_gambio_orderquery($db_transaction_data['trans_orderid'],''));

				$transaction_check = mysqli_query($dbconn, "select * from trx_transaction where transactionHash = '".$hash."'");
				
				// check if order exists
				if ((mysqli_num_rows($gambio_order_check) > 0) && (mysqli_num_rows($transaction_check) > 0)) {
			
				// extract gambio data
				$gambio_order_data = mysqli_fetch_assoc($gambio_order_check);
				
				// extract transaction data
				$transaction_entry = mysqli_fetch_assoc($transaction_check);
				
				$old_orderid = $transaction_entry['orderid'];
				
				// update orderstate
				$db_transaction_data = order_assignment($gambio_order_data,$transaction_entry,$dbconn,$shop_wallet_address,$db_transaction_data);	
				
				// generate sql query
				$dbquery = "UPDATE trx_transaction SET orderassignment = '0', transactionstate = '".$db_transaction_data['transaction_state']."', orderid = '".$db_transaction_data['trans_orderid']."' WHERE transactionHash = '".$hash."'";
				
				// update transaction state
				mysqli_query($dbconn, $dbquery);
				
				order_assignment_update($dbconn,$old_orderid,$shop_wallet_address);
				}
			}
		}	
		// action -> remove
		else if ($action == 'remove'){
			
			// generate sql query
			$dbquery = "UPDATE trx_transaction SET orderassignment = '0', transactionstate = 'TRX_TRANSACTIONTATE_6', orderid = '' WHERE transactionHash = '".$hash."'";

			// update transaction state
			mysqli_query($dbconn, $dbquery);
			
			// update order status
			order_assignment_update($dbconn,$orderid,$shop_wallet_address);

		}
		
	}	
?>