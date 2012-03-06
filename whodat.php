<?php
print get_whodat_status();

/**
* Check to see if they user is valid Marketo lead.
* If they are, we add a permanant cookie so we don't need to hit the Marketo API again.
* If they are not we, add a session cookie.
* The cookies allow us to cache the API response.
*
* @param string $cookie
* @return whodat data (JSON)
*/
function get_whodat_status($cookie = 'whodat')
{
	$retval = '{"result": false}';
	if (1==1 && isset($_COOKIE[$cookie]))
	{
		// If the cookie is set, return its value.
        if (get_magic_quotes_gpc() == true) {
            $whodat_json = stripslashes($_COOKIE[$cookie]);
        } else {
            $whodat_json = $_COOKIE[$cookie];
        }
		$retval = json_encode(array("result" => true, "from" => "cookie", "data" => json_decode($whodat_json)));
	}
	else
	{
		// We access the Marketo API with the user's Marketo cookie to see who they are
		if(isset($_COOKIE['_mkto_trk']))
		{
			// We only include the Marketo API class if it's needed.
			include_once('class.marketoapi.php');

			// What cookie type do we need to set?
			// Valid types are:
			//  - permanant
			//  - session
			$cookie_type = '';

			$marketo_api = new MarketoAPI();
			$result = $marketo_api->getLead('COOKIE', $_COOKIE['_mkto_trk']);
            $data = $result->result->leadRecordList->leadRecord;
            $whodat = array("Id" => $data->Id, "Email" => $data->Email);
            // can we get a name?
            foreach ($data->leadAttributeList->attribute as $attribute) {
                //only record stuff we're truly interested in
                if ((bool)array_search($attribute->attrName, array("{dummy}","FirstName","LastName","InferredCompany","InferredCountry","Company","Country"))) {
                    $whodat[$attribute->attrName] = $attribute->attrValue;
                }
            };
            $a = array("result" => true, "from" => "marketo", "data" => $whodat);
            
            $retval = json_encode($a);
			
            // If the response looks valid we check for a valid email address in the response.
			if (TRUE === $marketo_api->doesResponseLookValid($result))
			{
                $cookie_type = 'session';
            }
            else
            {
                $cookie_type = 'session';
            }
			
			// set the cookie so it can be accessed by all subdomains.
			$cookie_domain = '.owlstonenanotech.com';

			if ('permanant' == $cookie_type)
			{
				setcookie($name = $cookie, $value = json_encode($whodat), $expire = (time() + (60 * 60 * 24 * 365)), $path = '/', $domain = $cookie_domain);
			}
			else if ('session' == $cookie_type)
			{
				setcookie($name = $cookie, $value = json_encode($whodat), $expire = 0, $path = '/', $domain = $cookie_domain);
			}
		}
		else
		{
			$retval = '{"result": true, "from": null, "data": null, "msg": "No Marketo cookie"}';
		}
	}

	return $retval;
}

?>