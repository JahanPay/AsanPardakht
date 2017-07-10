<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {


	public function index()
	{
		$this->output->enable_profiler(false);
		if( ! empty($_POST['price']) and $_POST['price']>=100 and is_numeric($_POST['price']))
		{
			$data = array(
				'au'=>str_replace('.','_temp_',microtime(true)-1300000000) ,
				'price'=> (int) $_POST['price'] ,
				'description'=> strip_tags($_POST['desc']) ,
				'name'=> strip_tags($_POST['name']) ,
				'phone'=> strip_tags($_POST['mob']) ,
				'email'=> strip_tags($_POST['email']) ,
				'time'=> time(),
				'status'=> 0 ,
			);
			 $this->db->insert('order', $data);
			
			unset($data['description'] , $data['name'] , $data['phone'] , $data['email']  );
			$query = $this->db->get_where('order', $data, 1)->row();
			//print_r($query->id);
			
			
			$api = $this->config->item('jahanpay_api') ;
			$amount = $data['price'] ; //Tooman
			$callbackUrl = site_url('welcome/back/'.$data['time']).'?order_id='.$query->id;
			$orderId = $query->id;
			
			
			$client = new SoapClient("http://www.jpws.me/directservice?wsdl");
			$res = $client->requestpayment($api , $amount , $callbackUrl , $orderId );
			if($res['result']!=1)
			{
				$res = array_map('urldecode',$res);
				print_r($res);
				die;
			}
			
			$this->db->where('id', $query->id);
			$this->db->update('order', array('au'=>$res['au'])); 
			
			die("plz w8...<div style='display:none'>".$res['form']."<script language='javascript'>document.jahanpay.submit()</script>");
		}
		$this->load->view('welcome_message');
	}
	
	function back($time = 0)
	{
		$time = (int) $time;
		
		$id = (int) $_GET['order_id'];
		$data = array(
			'time'=>$time ,
			'id'=>$id ,
			'status'=> 0 ,
		);
		$query = $this->db->get_where('order', $data, 1)->row();
		
		$out = array();
		if(empty($query))
			$out['status']=0;
		else
		{
			$client = new SoapClient("http://www.jpws.me/directservice?wsdl");
			$result = $client->verification($this->config->item('jahanpay_api') , $query->price , $query->au , $query->id, $_POST + $_GET );
			if( ! empty($result) and $result['result']==1)
			{
				$out['status'] =1;
				$out['au'] =1;
				$_GET['au'] = $res['bank_au'];
				$this->db->where('id', $id);
				$this->db->update('order', array('status'=>1)); 
			}
			else
			{
				$out['status'] =0;
			}
		}
		
		//print_r($out);
		$this->load->view('back',$out);
		
		
		
	}
	
	
}


/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */