<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as Controller;
use Illuminate\Http\Request;

class ApiController extends Controller
{

	protected $provider_list = array(array('imgur', 'https://imgur.com/'), array('flickr', 'https://flickr.com'), array('google', 'https://images.google.com'), array('pixabay', 'https://www.pixabay.com'));

	public function images(Request $request)
	{
		$search = $request->input('q');
		$page = $request->input('page');
		if (empty($page)) 
			$page=0;
		$limit = $request->input('limit');
		if (empty($limit)) 
			$limit=10;

/*
		foreach ($this->provider_list as $provider) {
			$x=$provider[0];
			$newoutput = $this->$x($search, $page, $limit);
			$output = array_merge($output, $newoutput);
		}
*/
		//return response()->json($output);
$output = $this->google($search, $page, $limit);

		$output['records'] = count($output);
		return $output;
	}

	public function providers()
	{
		return response()->json($this->provider_list);
	}

	public function flickr($search, $page, $limit) 
	{
		$url = 'https://api.flickr.com/services/rest/?method=flickr.photos.search&api_key='.env('FLICKR_KEY').'&text='.$search.'&format=json&nojsoncallback=1&per_page='.$limit.'&page='.$page;

		$data = $this->curl($url);

		$array = json_decode($data);

		$count = 0;
		$return = array();

		foreach ($array->photos->photo as $current) {

                        $return[$count]['title'] = $current->title;
                        $return[$count]['url'] = 'https://www.flickr.com/photos/'.$current->owner.'/'.$current->id;
                        $return[$count]['image'] = 'https://farm'.$current->farm.'.staticflickr.com/'.$current->server.'/'.$current->id.'_'.$current->secret.'.jpg';
                        $return['type'] = 'image/jpeg';
                        $return['provider'] = 'flickr';

                        $count++;
                }

		return $return;

	}

	public function imgur($search, $page, $limit) 
	{
		$url = 'https://api.imgur.com/3/gallery/search/?q='.$search.'&perpage='.$limit.'&page='.$page;
		$clientid = env('IMGUR_ID');

		$data = $this->curl($url, $clientid);

                $array = json_decode($data);

                $count = 0;
                $return = array();

                foreach ($array->data as $current) {

                        $return[$count]['title'] = $current->title;
                        $return[$count]['url'] = $current->link;
                        $return[$count]['image'] = $current->images[0]->link;
                        $return['type'] = $current->images[0]->type;
                        $return['provider'] = 'imgur';

                        $count++;
                }

		return $return;

	}

	public function pixabay($search, $page, $limit) 
	{
		$curpage = $page+1;
		$url = 'https://pixabay.com/api/?key='.env('PIXABAY_KEY').'&q='.$search.'&image_type=photo&per_page='.$limit.'&page='.$curpage;

		$data = $this->curl($url);
                $array = json_decode($data);
                
                $count = 0;
                $return = array();

                foreach ($array->hits as $current) {
                        $return[$count]['title'] = $current->pageURL;
                        $return[$count]['url'] = $current->pageURL;
                        $return[$count]['image'] = $current->largeImageURL;
                        $return['type'] = 'image/jpeg';
                        $return['provider'] = 'pixabay';
        
                        $count++;
                }

		return $return;

	}

	public function google($search, $page, $limit) 
	{
		$lowRange = $page * $limit;
		$highRange = $lowRange + $limit;
		$url = 'https://www.googleapis.com/customsearch/v1?q='.$search.'&key='.env('GOOGLE_KEY').'&cx='.env('GOOGLE_CX').'&lowRange='.$lowRange.'&highRange='.$highRange;

		$data = $this->curl($url);

                $array = json_decode($data);

                $count = 0;
                $return = array();

                foreach ($array->items as $current) {

                        $return[$count]['title'] = $current->title;
                        $return[$count]['url'] = $current->link;
                        $return[$count]['image'] = $current->pagemap->cse_image[0]->src;
                        $return['type'] =  'image/jpeg';
                        $return['provider'] = 'google';

                        $count++;
                }

		return $return;

	}

	protected function curl($url, $clientid = '') 
	{
		// create curl resource 
		$ch = curl_init(); 

		// set url 
		curl_setopt($ch, CURLOPT_URL, $url); 
		if (!empty($clientid)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Client-ID ' . $clientid));
		}

		//return the transfer as a string 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

		// $output contains the output string 
		$output = curl_exec($ch); 

		// close curl resource to free up system resources 
		curl_close($ch);  

		return $output;

	}
}
