<?php
/*
* This is an Admin Table Library
* 
*
*/
if ( ! defined( 'ABSPATH' ) ) exit;


class Biciteca_List_Table extends WP_List_Table {
    /**
    * Constructor
    * @param string $post_type Post type
    * @param array $columns table columns
    * @param string $singular singular name
    * @param string $plural plural name
    */

    public $columns;
    public $post_type;
    public $_column_headers;
    public $items;

	function __construct($post_type = '', $columns = array(), $singular = '', $plural = '') {
       parent::__construct( array(
      'singular'=> $singular, 
      'plural' => $plural,
      'ajax'   => true 
      ) );
       
       $this->columns = $columns;
       $this->post_type = $post_type;
       $this->prepare_items();
      
    }

   	public function prepare_items()
    {
        //$columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();

        $this->_column_headers = array($this->columns, $hidden, $sortable);
        $this->items = $data;
    }

    public function get_columns()
    {
        $columns = array(
            'id'            => 'ID',
            'name'          => 'Name',
            'address'       => 'Address',
            'membership_type' => 'Type of Membership'
        );

        return $columns;
    }

    public function get_hidden_columns()
    {
        return array('id');
    }

    public function get_sortable_columns()
    {
        #return array('name' => array('name', false));
        return array();
    }

    private function table_data()
    {
        $data = array();

        $posts = get_posts(array('post_type' => $this->post_type));
        foreach ($posts as $post):
            $postdata = array();
            
            foreach ( $this->columns as $key => $values):
                if ($key == 'id'){
                    $postdata[$key] = $post->ID;
                }
                elseif ($key == 'name') {
                    $postdata[$key] = $post->post_title;
                }else{
                    $postdata[$key] = get_post_meta($post->ID, $key)[0];
                }
            endforeach;
            $data[] = $postdata;
            /*$data[] = array(
                'id' => $post->ID,
                'name' => $post->post_title,
                'address' => get_post_meta($post->ID, 'address')[0],
                'membership_type' => get_post_meta($post->ID, 'membership_type')[0]
                );*/
        endforeach;

        return $data;
    }

    public function column_id($item)
	{
    	return $item['name'];
	}

	public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'name':
                return '<a href="?page=biciteca-edit-'. $this->post_type . '&id=' . $item["id"] .'">' . $item[ $column_name ] . '</a>';
            default:
                return $item[ $column_name ];

           /* default:
                return print_r( $item, true ) ;*/
        }
    }


}
 ?>
