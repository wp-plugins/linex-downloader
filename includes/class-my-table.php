<?php
defined("ABSPATH")||die("invalid access");

	class LinexMyListTable extends Linex_WP_List_Table{
		
		function __construct() {
			parent::__construct(array(
				'singular' => 'download', //Singular label
				'plural' => 'downloads', //plural label, also this well be one of the table css class
				'ajax' => false //We won't support Ajax for this table
			));
		}
		function extra_tablenav($which) {
			if ($which == "top") {
				//The code that goes before the table is here
				//echo"Hello, I'm before the table";
			}
			if ($which == "bottom") {
				//The code that goes after the table is there
				//echo"Hi, I'm after the table";
			}
		}

		function get_columns() {
			return $columns= array(
			   'id'=>__('id',"linex-downloader"),
			   'title'=>__('title',"linex-downloader"),
			   'file_name'=>__('file name',"linex-downloader"),
			   'file_size'=>__('file size',"linex-downloader"),
			   'start_time'=>__('start time',"linex-downloader"),
			   'finish_time'=>__('finish time',"linex-downloader"),
			   'url'=>__('URL',"linex-downloader"),
			  // 'is_finished'=>__('is finished',"linex-downloader"),
			   'manage'=>__('manage',"linex-downloader"),
			);
		}
		/*
		public function get_sortable_columns() {
			return $sortable = array(
				'id'=>'id',
				'is_finished'=>'is_finished',
				'start_time'=>'start_time'
			);
		}
		*/
		function prepare_items() {
			global $wpdb, $column_headers;
			$screen = get_current_screen();
			
			$tablename = $wpdb->prefix."linex_downloader";
			/* -- Preparing your query -- */
			$query = "SELECT * FROM $tablename";

			/* -- Ordering parameters -- */
			//Parameters that are going to be used to order the result
			$orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'ASC';
			$order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : '';
			if (!empty($orderby) & !empty($order)) {
				$query.=' ORDER BY ' . $orderby . ' ' . $order;
			}else{
				$query.=' ORDER BY id desc ';
			}

			/* -- Pagination parameters -- */
			//Number of elements in your table?
			$totalitems = $wpdb->query($query); //return the total number of affected rows
			//How many to display per page?
			$perpage = 10;
			//Which page is this?
			$paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
			//Page Number
			if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
				$paged = 1;
			}
			//How many pages do we have in total?
			$totalpages = ceil($totalitems / $perpage);
			//adjust the query to take pagination into account
			if (!empty($paged) && !empty($perpage)) {
				$offset = ($paged - 1) * $perpage;
				$query.=' LIMIT ' . (int) $offset . ',' . (int) $perpage;
			}

			/* -- Register the pagination -- */
			$this->set_pagination_args(array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page" => $perpage,
			));
			//The pagination links are automatically built according to those parameters

			/* -- Register the Columns -- */
			$columns = $this->get_columns();
			$this->_column_headers = array($columns, $hidden, $sortable);
			$column_headers[$screen->id] = $columns;

			/* -- Fetch the items -- */
			$this->items = $wpdb->get_results($query);
		}
		function display_rows() {

			//Get the records registered in the prepare_items method
			$records = $this->items;

			//Get the columns registered in the get_columns and get_sortable_columns methods
			$columns= $this->get_columns();
			$hidden = array();
			//var_dump($columns);
			//Loop for each record
			if (!empty($records)) {
				foreach ($records as $rec) {

					//Open the line
					echo '<tr id="record_' . $rec->id . '">';
					$upload_dir = wp_upload_dir();
					//var_dump($upload_dir);
					$file = $upload_dir['baseurl'].DIRECTORY_SEPARATOR."linex_downloader"
					.DIRECTORY_SEPARATOR.$rec->file_name;
					$url = admin_url('admin.php?page=linex-downloader&action=delete&id='.$rec->id);
					$url = wp_nonce_url( $url,'delete',"nonce");
					
					$deleteLink = '<a href="'.$url.'">'.__("Delete","linex-downloader").'</a>';
					foreach ($columns as $column_name => $column_display_name) {

						//Style attributes for each col
						$class = "class='$column_name column-$column_name'";
						$style = "";
						//if (in_array($column_name, $hidden))
							//$style = ' style="display:none;"';
						$attributes = $class . $style;

						//edit link
						//$editlink = '/wp-admin/link.php?action=edit&link_id=' . (int) $rec->link_id;
						//echo $column_name." ";
						//Display the cell
						switch ($column_name) {
							case "id": echo '<td ' . $attributes . '>' . stripslashes($rec->id) . '</td>';
								break;
							case "title": echo '<td ' . $attributes . '>' . stripslashes($rec->title) . '</td>';
								break;
							case "file_name": 
								
								echo '<td ' . $attributes . '>'."<a target='_blank' href='$file'>". stripslashes($rec->file_name) . '</a></td>';
								break;
							case "file_size": echo '<td ' . $attributes . '>' . $this->formatBytes($rec->file_size) . '</td>';
								break;
							case "start_time": echo '<td ' . $attributes . '>' . stripslashes($rec->start_time) . '</td>';
								break;
							case "finish_time": echo '<td ' . $attributes . '>' . stripslashes($rec->finish_time) . '</td>';
								break;
							case "url": echo '<td ' . $attributes . ' ><a href="' . stripslashes($rec->url).'">'.__("show","linex-downloader").'</a>' . '</td>';
								break;
							//case "is_finished": echo '<td ' . $attributes . '>' . stripslashes($rec->is_finished) . '</td>';
							//	break;
							case "manage": echo '<td ' . $attributes . '>' .$deleteLink. '</td>';
								break;

						}
					}

					//Close the line
					echo'</tr>';
				}
			}
		}
		function formatBytes($bytes, $precision = 2) {
			//die("aaaa");
			$units = array(
				__('B',"linex-downloader"),
				__('KB',"linex-downloader"),
				__('MB',"linex-downloader"),
				__('GB',"linex-downloader"),
				__('TB',"linex-downloader")
			);
			
			$bytes = max($bytes, 0);
			$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
			$pow = min($pow, count($units) - 1);

			// Uncomment one of the following alternatives
			$bytes /= pow(1024, $pow);
			// $bytes /= (1 << (10 * $pow)); 

			return round($bytes, $precision) . ' ' . $units[$pow];
		}



	}
