<?php

//Configurables
$ldaprdn  = 'user@domain.com';     // ldap rdn or dn
$ldappass = 'password';  // associated password
$ldapserver = 'domain.com'; // LDAP Server
$dn = "OU=Users,DC=domain,DC=com"; // Base DN

$deskuser = 'user@domain.com'; // Desk.com User
$deskpass = 'password'; // Desk.com User Password
$deskDomain = 'example.desk.com'; // Desk.com Subdomain

// LDAP Connection
$ldapconn = ldap_connect($ldapserver)
    or die("Could not connect to LDAP server.");

if ($ldapconn) {

    // Binding to LDAP server
    $ldapbind = ldap_bind($ldapconn, $ldaprdn, $ldappass);

    // Verify binding
    if ($ldapbind) {

    $filter="(objectClass=user)"; // Get only Users
    $justthese = array("displayName", "mail"); // Get Users Display Name and Email Address

    $sr=ldap_search($ldapconn, $dn, $filter, $justthese);
    $info = ldap_get_entries($ldapconn, $sr);

for ($i=0; $i<$info["count"]; $i++)
    {

        list($firstname, $lastname) = explode(" ", $info[$i]["displayname"][0], 2); // Split First name and Surname
        $mail = $info[$i]["mail"][0]; // Create Mail Variable (looks nicer)
//Create Array
$users = array(
    "first_name" => $firstname,
    "last_name" => $lastname,

        "emails" =>
                array(
                        array( "type" => "work",
                                "value" => $mail
                                )
             )
);

$data_string = json_encode($users); // Create data for cURL by converting PHP Array to JSON

//Using CuRL to send JSON data to Desk.com 
$ch = curl_init("https://$deskDomain/api/v2/customers");  
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ; 
curl_setopt($ch, CURLOPT_USERPWD, "$deskuser:$deskpass");                                                                    
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
    'Content-Type: application/json',                                                                                
    'Content-Length: ' . strlen($data_string))                                                                       
);                                                                                                                   
 
$result = curl_exec($ch);

echo "$firstname $lastname ";
echo $result;
echo "\n";

if ( $result == '{"message": "Too Many Requests"}') { //Desk.com's API limits are explained here http://dev.desk.com/API/using-the-api/#rate-limits
	echo "\n";
	echo "API Limit Reached, Pausing for 60 seconds";
	sleep(60);
	$result = curl_exec($ch);
}

}

    } else {
        echo "LDAP bind failed...";
    }

}

?>