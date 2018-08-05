<?php

//include the HTML DOM scraper framework
include_once('includes/simple_html_dom.php');
$first_run = 1;
$exhausted_array = 0;
$random_number = null;
$proxies_array = array();
if (ob_get_level() == 0) ob_start();

//function to scrape proxy page
function scrape_nova_proxy()
{
    // create HTML DOM
    $html = file_get_html('https://www.proxynova.com/proxy-server-list/country-us/');
    $ret = array();

    // get news block
    foreach($html->find('table[id=tbl_proxy_list] tbody tr') as $_proxy)
    {
        //check if this row is an advertisement...
        $_advertisement = (($_proxy->find('td', 0)->colspan == 10) ? 1 : 0);
        //skip advertisement rows
        if($_advertisement)
        {
            continue;
        }
        //get up-time
        $item['up-time'] = rtrim(trim($_proxy->find('td', 4)->find('span',0)->plaintext),'%');
        //check if the up-time is less than 90%, if so, skip this server
        if($item['up-time'] < '90')
        {
        	continue;
        }
        // get proxy server address
        $item['server'] = trim($_proxy->find('td', 0)->find('abbr',0)->getAttribute('title'));
        // get proxy server port
        $item['port'] = trim($_proxy->find('td', 1)->plaintext);
        //pust the item into the return array
        $ret[] = $item;
    }
    
    // clean up memory
    $html->clear();
    unset($html);
    //return the array containing all of the proxy servers
    return $ret;
}

function run_scrape(){
    global $first_run;
    global $proxies_array;
    global $exhausted_array;
    global $random_number;
    //only scrape the proxy site once per run
    if($first_run == 1 || $exhausted_array == 1)
    {
        echo 'Scraping Nova Proxy...';
        ob_flush();
        flush();
        //scrape the nova proxy site
        $proxies_array = scrape_nova_proxy();
        echo 'done.<br />';
        ob_flush();
        flush();
        $first_run = 0;
    }
    echo "Picking a random proxy...";
    ob_flush();
    flush();
    //select a proxy at random
    $random_number = array_rand($proxies_array);
    $random_proxy = $proxies_array[$random_number];
    echo "done.<br />";
    ob_flush();
    flush();
    echo 'Building proxy connection string...';
    ob_flush();
    flush();
    //build the proxy connection string
    $proxy_string = $random_proxy['server'] . ':' . $random_proxy['port'];
    echo 'done. ' . $proxy_string . '<br />';
    ob_flush();
    flush();
    echo 'Attempting curl with proxy connection...';
    ob_flush();
    flush();
    //execute curl command
    $ch = curl_init();
    // set URL
    curl_setopt($ch, CURLOPT_URL, "https://api.weather.gov/zones/forecast/KYZ049/forecast");
    // set proxy connection
    curl_setopt($ch, CURLOPT_PROXY, $proxy_string);
    // http request timeout 10 seconds
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    // set user agent
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.84 Safari/537.36");
    // do not return header response
    curl_setopt($ch, CURLOPT_HEADER, 0);
    // If url has redirects then go to the final redirected URL.
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    //ignore ssl cert errors
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    // Do not outputting it out directly on screen.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // download the file
    $file = curl_exec($ch);
    // close cURL resource, and free up system resources
    curl_close($ch);
    echo 'Curl completed.<br />';
    ob_flush();
    flush();
    return $file;
}

while (true)
{
    global $proxies_array;
    global $exhausted_array;
    global $random_number;
    $file_scrape = run_scrape();
    if (strpos($file_scrape, 'forbidden') !== false || $file_scrape == null || $file_scrape == '') {
        echo 'Curl could not download the file using this proxy. <span style="color: red; font-weight: bold">Retrying</span>...<br />';
        echo $file_scrape;
        echo 'Removing proxy from list...';
        unset($proxies_array[$random_number]);
        echo 'done.<br />';
        if(count($proxies_array==0))
        {
            $exhausted_array = 1;
            echo 'No more servers left, re-scraping Nova...<br />';
        }
        ob_flush();
        flush();
        //retry after 1 seconds
        sleep(1);
    } else {
        echo '<span style="color: green; font-weight: bold;">SUCCESSFUL DOWNLOAD!</span><br /><br />';
        echo $file_scrape;
        //save file to filesystem
        $fp = fopen('../data.txt', 'w');
        fwrite($fp, $file_scrape);
        fclose($fp);
        break;
    }
}

ob_end_flush();

?>