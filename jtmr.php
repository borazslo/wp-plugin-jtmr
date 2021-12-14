<?php 
/**
 * Plugin Name: JTMR features
 * Description: A magyar jezsuitákhoz kapcsolódó honlapokban közös egyedi lehetőségek
 * Version: 0.1
 * Requires at least: 4.0
 * Requires PHP: 5.6
 * Author: Elek László SJ
 * Text Domain: JTMR
 */
 
 
 class Webgalamb {
	//https://www.webgalamb.hu/wg8plus-sugo/?page=rest-api
	private $apiPath = 'restclient.wg.class.php';
	private $apiUrl = 'https://manreza.hu/webgalamb4/rapi.php';
	private $apiToken = '***';
	private $apiSecret = '***';	

	public $groups = array(
		array(86, '- TEST evoCRM ---', 'CSAK EZT HASZNÁLD EGYELŐRE! Légyszi légyszi!'),
		array(49,'Provinciánk hírei', 'PH lev.lista. leírása'),
		array(6,'Jezsuita levelek','Vajon mik lehetnek ezek?'),
		array(2, 'Manréza hírlevél','Híreink a dobogókői Manréza lelkigyakorlatos házból'),
		array(96, 'Jezsuita kiadó hírlevele','Új publikációk, kiadványok, kedvezmények és meglepetések')
	 );
	public $defaultGroup = 49;
	
	function __construct() {
		$this->initFields();
	
		require_once($this->apiPath);		
	}
	
	function initFields() {
			// name => [title, type, required, pattern, patternTitle, options]
		 $namePattern = '/^[A-Za-z\x{00C0}-\x{00FF}][A-Za-z\x{00C0}-\x{00FF}\'\-]+([\ A-Za-z\x{00C0}-\x{00FF}][A-Za-z\x{00C0}-\x{00FF}\'\-]+)*/u';
		 $this->fields = array(
				'mail' => array('E-mail cím','text','required','/^[a-z0-9._%+-]+\@[a-z0-9.-]+\.[a-z]{2,4}$/i',false),
				'salutation' => array('Titulus','select',false,false,false, array('','Card.','Dr.','Dr. habil.','Dr. Med.','Dr. prof.','id.','id. Dr.','ifj.','ifj. Dr.','Mag. phil.','Mr.','Mr. & Mrs.','Mrs.','Ms.','Msgr.','özv.','özv. Dr.','P.','Prof.','Prof. Dr.')),
				'lastName' => array('Vezetéknév', 'text','required',$namePattern,''),
				'firstName' => array('Keresztnév', 'text','required',$namePattern,''),
				'countryCode' => array('Ország', 'select',false, false, false, array_merge(array(''=>'','HU'=>'Magyarország','RO'=>'Románia','SK'=>'Szlovákia','--'=>'--'),$this->countries)),
				'postalCode' => array('Irányítószám', 'text'),
				'city' => array('Város', 'text'),
				'street' => array('Cím', 'text')
			);

	}
	
	public $errorMessages = [];
	
	function formHtml() {
	  
		echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post" >';
		
		echo '<table border="0" cellspacing="0" cellpadding="4">
		<tbody>';
		
		//Webgalamb felirakozási lehetőségek listája		
		foreach($this->groups as $group) {
			echo "<tr><td><input type='checkbox' name='wg-groups[]' value='".$group[0]."' "; 
			if( ( isset($_POST['wg-groups']) AND in_array($group[0],$_POST['wg-groups']) ) 
				OR ( $group[0] == $this->defaultGroup) ) echo " checked ";
			echo "></td>";
			echo "<td>";
			if(  $group[0] == $this->defaultGroup) echo "<strong>".$group[1]."</strong>";
			else echo $group[1];
			
			echo "<br/><small>".$group[2]."</small></td></tr>";
		}
		
		//Webgalamb mezők kitöltése	
		foreach($this->fields as $name => $field) {
			echo "<tr>";
			echo "<td>".$field[0].":";
			
			if ( isset($field[2]) AND $field[2] == "required") 
				echo " <font color='red' title='Ennek a mezőnek a kitöltése kötelező!'>*</font>";
			
			echo"</td>";
			echo "<td>";
			
			if($field[1] == 'select') {
				echo "<select name='wg-".$name."'>";
				if(isAssoc($field[5])) $isAssoc = true; else $isAssoc = false;
				foreach( $field[5] as $selectValue => $selectName) {				
					if($isAssoc == false) $selectValue = $selectName;
					echo "<option value='".$selectValue."' "; 
					if(isset($_POST["wg-".$name]) AND $selectValue == $_POST["wg-".$name]) echo "selected";
					echo ">".$selectName."</option>\n";
				}
				echo "</select>";
				
			} else {
				echo "<input 
				type='".$field[1]."' 
				name='wg-".$name."' 
				". ( isset($field[2]) ? " ".$field[2] : "" ) ." 
				". ( isset($field[3]) ? " pattern = '".$field[3]."' title='".$field[4]."'" : "" ) ." 
				value='". ( isset( $_POST["wg-".$name] ) ? esc_attr( $_POST["wg-".$name] ) : '' )."'>";
			}
				
			echo "</td>";
			echo "</tr>";
		
			
		}
		
		echo "<tr><td><input type='checkbox' name='wg-gdpr' value='elfogadom'> Elfogadom</td><td>Elolvastam, elfogadtam. Örülök neked. Avagy a GDPR kompatibilitás</td></tr>";
		
		echo '</table>';
		echo '<input type="submit" name="wg-sub" value="Feliratkozás"/>';
		echo '</form>';
	}
	
	public function validateForm() {
		$isValid = true;
		//Ellenőrizzük a felirakozási listák azonosítóit. Ne legyen itt semmi család.
		if(!isset($_POST['wg-groups'])) {$isValid = false; $this->errorMessages[] = 'Nincs hírlevél lista kiválasztva!'; }
		elseif(!is_array($_POST['wg-groups'])) { $isValid = false; die('WG Groups is not an array!'); }
		else {
			foreach($this->groups as $group) $groupIDs[] = $group[0];
			foreach($_POST['wg-groups'] as $groupID) 
				if(!in_array($groupID, $groupIDs)) { $isValid = false;  die('There is no such a wg group.'); }				
		}
		
		//Ellenőrizzük az egyes mezőket úgy általában
		foreach($this->fields as $name => $field) {
			if( isset($field[2]) AND $field[2] == 'required' AND ( !isset($_POST['wg-'.$name]) OR $_POST['wg-'.$name] == null )) 
			{ $isValid = false; $this->errorMessages[] = "A(z) '". $field[0] . "' kitöltése kötelező!";			}
			else if( 
				isset($_POST['wg-'.$name]) AND $_POST['wg-'.$name] != null
				AND isset($field[3]) AND $field[3] != false AND !preg_match($field[3],$_POST['wg-'.$name])
				)
				{ $isValid = false;  $this->errorMessages[] = ( isset($field[4]) AND $field[4] != false ) ? "Hibás formátum! ". $field[4] : "A(z) '". $field[0] . "' nincs megfelelően kitöltve!"; }
			else if ( 
				isset($_POST['wg-'.$name]) AND $_POST['wg-'.$name] != null
				AND $field[1] == 'select' ) {
					if(isAssoc($field[5])) {
						$field[5] = array_keys($field[5]);						
					}
					if(!in_array($_POST['wg-'.$name],$field[5])) { $isValid = false; die("A ".$name." mező nem vehet fel ilyen értéket!"); }								
				}
		}
		
		//Ellenőrizzük, hogy a cím bármely részét elkezdte kitölteni, akkor mind ki van-e töltve.
		if( isset($_POST['wg-countryCode']) AND $_POST['wg-countryCode'] == '--') unset($_POST['wg-countryCode']);
		if( 
			(
				( isset($_POST['wg-postalCode']) AND $_POST['wg-postalCode'] != null )
				OR ( isset($_POST['wg-city']) AND $_POST['wg-city'] != null )
				OR ( isset($_POST['wg-street']) AND $_POST['wg-street'] != null )
				OR ( isset($_POST['wg-countryCode']) AND $_POST['wg-countryCode'] != null )
			) AND (
				 !isset($_POST['wg-postalCode']) OR $_POST['wg-postalCode'] == null 
				 OR !isset($_POST['wg-city']) OR $_POST['wg-city'] == null 
				 OR !isset($_POST['wg-street']) OR $_POST['wg-street'] == null 
				 OR !isset($_POST['wg-countryCode']) OR $_POST['wg-countryCode'] == null 
			)
		) {
			$isValid = false;
			$this->errorMessages[] = "Kérjük ha megadja nekünk a címét, akkor a címnek minden adatát adja meg (ország, város, irányítószám, utca és házszám)!";
		}
		
		//GDPR ellenőrzés
		if( !isset($_POST['wg-gdpr']) OR $_POST['wg-gdpr'] != 'elfogadom') {
			$isValid = false; 
			$this->errorMessages[] = "Kérjük olvassa át és fogadja el adatvédelmi és atakezelési nyilatkozatunkat4";
		}
		
		return $isValid;
	
	
	}
	
	function sendSubscriptions() {
	
	
		foreach($_POST['wg-groups'] as $groupID) {
			$this->api = new WebgalambRestClient($this->apiUrl, $this->apiToken, $this->apiSecret);
		
			$params = ['group_id' => $groupID, 'subscriber_data' => [] ];
			
			foreach($this->fields as $name => $field) {
				if(isset($_POST['wg-'.$name]) AND $_POST['wg-'.$name] != null) {
					$params['subscriber_data'][ $name ] = sanitize_text_field($_POST['wg-'.$name]);
				}
			}			
			$params['subscriber_data']['Feliratkozási URL'] =  $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			
			$result = $this->api->post('InsertSubscriber', $params);		
			if(isset($result['id'])) {
				echo "<strong>Kedves ".$params['subscriber_data']['firstName']." ".$params['subscriber_data']['lastName']."! Köszönjük a felirakozást!";			
			} else {
				// Már szerepel a Webgalambban
				if(isset($result['error']) AND $result['error'] == "Subscriber already exists") {
				
					$results = $this->api->get('GetSubscriber', ['subscriber' => $params['subscriber_data']['mail'] ]);
					if(isset($results['mail'])) $results = [0 => $results];
					foreach($results as $result) {
						if($result['g'] == $groupID) {
							if($result['active'] == 1) {
								echo "<div>Örömmel jelentjük, hogy már korábban (".$result['datum'].") megtörtént a feliratkozás erre a hírlevélre.</div>";
							} else if($result['active'] == 0) {								
									$result = $this->api->post('EditSubscriber', ['subscriber_data' => [
										'active' => 1, 
										'Feliratkozási URL' =>  $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] ],
										'subscriber'=>$params['subscriber_data']['mail'], 
										'group_id'=>$groupID]
									);
									if(isset($result['success'])) echo "<div>Jó újra látni ezen a listán!</div>";
									else echo "<div>Sajnos nem sikerült újra feliratkozni!</div>";							
							} else if ($result['active'] == 2) {
								echo "<div>Köszönjük a feliratkozást! Sajnos a renszerünk azt jelzi, hogy az ön címéről vissza szoktak pattani a leveleink és nem jutnak el önhöz. Kérjük, vegye fel velünk a kapcsolatot!</div>";
							}
						}
					}
				
				}
			
			}
	
		}
	
	}
	
	public $countries = array //https://gist.github.com/vxnick/380904
	(
		'AF' => 'Afghanistan',
		'AX' => 'Aland Islands',
		'AL' => 'Albania',
		'DZ' => 'Algeria',
		'AS' => 'American Samoa',
		'AD' => 'Andorra',
		'AO' => 'Angola',
		'AI' => 'Anguilla',
		'AQ' => 'Antarctica',
		'AG' => 'Antigua And Barbuda',
		'AR' => 'Argentina',
		'AM' => 'Armenia',
		'AW' => 'Aruba',
		'AU' => 'Australia',
		'AT' => 'Austria',
		'AZ' => 'Azerbaijan',
		'BS' => 'Bahamas',
		'BH' => 'Bahrain',
		'BD' => 'Bangladesh',
		'BB' => 'Barbados',
		'BY' => 'Belarus',
		'BE' => 'Belgium',
		'BZ' => 'Belize',
		'BJ' => 'Benin',
		'BM' => 'Bermuda',
		'BT' => 'Bhutan',
		'BO' => 'Bolivia',
		'BA' => 'Bosnia And Herzegovina',
		'BW' => 'Botswana',
		'BV' => 'Bouvet Island',
		'BR' => 'Brazil',
		'IO' => 'British Indian Ocean Territory',
		'BN' => 'Brunei Darussalam',
		'BG' => 'Bulgaria',
		'BF' => 'Burkina Faso',
		'BI' => 'Burundi',
		'KH' => 'Cambodia',
		'CM' => 'Cameroon',
		'CA' => 'Canada',
		'CV' => 'Cape Verde',
		'KY' => 'Cayman Islands',
		'CF' => 'Central African Republic',
		'TD' => 'Chad',
		'CL' => 'Chile',
		'CN' => 'China',
		'CX' => 'Christmas Island',
		'CC' => 'Cocos (Keeling) Islands',
		'CO' => 'Colombia',
		'KM' => 'Comoros',
		'CG' => 'Congo',
		'CD' => 'Congo, Democratic Republic',
		'CK' => 'Cook Islands',
		'CR' => 'Costa Rica',
		'CI' => 'Cote D\'Ivoire',
		'HR' => 'Croatia',
		'CU' => 'Cuba',
		'CY' => 'Cyprus',
		'CZ' => 'Czech Republic',
		'DK' => 'Denmark',
		'DJ' => 'Djibouti',
		'DM' => 'Dominica',
		'DO' => 'Dominican Republic',
		'EC' => 'Ecuador',
		'EG' => 'Egypt',
		'SV' => 'El Salvador',
		'GQ' => 'Equatorial Guinea',
		'ER' => 'Eritrea',
		'EE' => 'Estonia',
		'ET' => 'Ethiopia',
		'FK' => 'Falkland Islands (Malvinas)',
		'FO' => 'Faroe Islands',
		'FJ' => 'Fiji',
		'FI' => 'Finland',
		'FR' => 'France',
		'GF' => 'French Guiana',
		'PF' => 'French Polynesia',
		'TF' => 'French Southern Territories',
		'GA' => 'Gabon',
		'GM' => 'Gambia',
		'GE' => 'Georgia',
		'DE' => 'Germany',
		'GH' => 'Ghana',
		'GI' => 'Gibraltar',
		'GR' => 'Greece',
		'GL' => 'Greenland',
		'GD' => 'Grenada',
		'GP' => 'Guadeloupe',
		'GU' => 'Guam',
		'GT' => 'Guatemala',
		'GG' => 'Guernsey',
		'GN' => 'Guinea',
		'GW' => 'Guinea-Bissau',
		'GY' => 'Guyana',
		'HT' => 'Haiti',
		'HM' => 'Heard Island & Mcdonald Islands',
		'VA' => 'Holy See (Vatican City State)',
		'HN' => 'Honduras',
		'HK' => 'Hong Kong',
		'HU' => 'Hungary',
		'IS' => 'Iceland',
		'IN' => 'India',
		'ID' => 'Indonesia',
		'IR' => 'Iran, Islamic Republic Of',
		'IQ' => 'Iraq',
		'IE' => 'Ireland',
		'IM' => 'Isle Of Man',
		'IL' => 'Israel',
		'IT' => 'Italy',
		'JM' => 'Jamaica',
		'JP' => 'Japan',
		'JE' => 'Jersey',
		'JO' => 'Jordan',
		'KZ' => 'Kazakhstan',
		'KE' => 'Kenya',
		'KI' => 'Kiribati',
		'KR' => 'Korea',
		'KW' => 'Kuwait',
		'KG' => 'Kyrgyzstan',
		'LA' => 'Lao People\'s Democratic Republic',
		'LV' => 'Latvia',
		'LB' => 'Lebanon',
		'LS' => 'Lesotho',
		'LR' => 'Liberia',
		'LY' => 'Libyan Arab Jamahiriya',
		'LI' => 'Liechtenstein',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'MO' => 'Macao',
		'MK' => 'Macedonia',
		'MG' => 'Madagascar',
		'MW' => 'Malawi',
		'MY' => 'Malaysia',
		'MV' => 'Maldives',
		'ML' => 'Mali',
		'MT' => 'Malta',
		'MH' => 'Marshall Islands',
		'MQ' => 'Martinique',
		'MR' => 'Mauritania',
		'MU' => 'Mauritius',
		'YT' => 'Mayotte',
		'MX' => 'Mexico',
		'FM' => 'Micronesia, Federated States Of',
		'MD' => 'Moldova',
		'MC' => 'Monaco',
		'MN' => 'Mongolia',
		'ME' => 'Montenegro',
		'MS' => 'Montserrat',
		'MA' => 'Morocco',
		'MZ' => 'Mozambique',
		'MM' => 'Myanmar',
		'NA' => 'Namibia',
		'NR' => 'Nauru',
		'NP' => 'Nepal',
		'NL' => 'Netherlands',
		'AN' => 'Netherlands Antilles',
		'NC' => 'New Caledonia',
		'NZ' => 'New Zealand',
		'NI' => 'Nicaragua',
		'NE' => 'Niger',
		'NG' => 'Nigeria',
		'NU' => 'Niue',
		'NF' => 'Norfolk Island',
		'MP' => 'Northern Mariana Islands',
		'NO' => 'Norway',
		'OM' => 'Oman',
		'PK' => 'Pakistan',
		'PW' => 'Palau',
		'PS' => 'Palestinian Territory, Occupied',
		'PA' => 'Panama',
		'PG' => 'Papua New Guinea',
		'PY' => 'Paraguay',
		'PE' => 'Peru',
		'PH' => 'Philippines',
		'PN' => 'Pitcairn',
		'PL' => 'Poland',
		'PT' => 'Portugal',
		'PR' => 'Puerto Rico',
		'QA' => 'Qatar',
		'RE' => 'Reunion',
		'RO' => 'Romania',
		'RU' => 'Russian Federation',
		'RW' => 'Rwanda',
		'BL' => 'Saint Barthelemy',
		'SH' => 'Saint Helena',
		'KN' => 'Saint Kitts And Nevis',
		'LC' => 'Saint Lucia',
		'MF' => 'Saint Martin',
		'PM' => 'Saint Pierre And Miquelon',
		'VC' => 'Saint Vincent And Grenadines',
		'WS' => 'Samoa',
		'SM' => 'San Marino',
		'ST' => 'Sao Tome And Principe',
		'SA' => 'Saudi Arabia',
		'SN' => 'Senegal',
		'RS' => 'Serbia',
		'SC' => 'Seychelles',
		'SL' => 'Sierra Leone',
		'SG' => 'Singapore',
		'SK' => 'Slovakia',
		'SI' => 'Slovenia',
		'SB' => 'Solomon Islands',
		'SO' => 'Somalia',
		'ZA' => 'South Africa',
		'GS' => 'South Georgia And Sandwich Isl.',
		'ES' => 'Spain',
		'LK' => 'Sri Lanka',
		'SD' => 'Sudan',
		'SR' => 'Suriname',
		'SJ' => 'Svalbard And Jan Mayen',
		'SZ' => 'Swaziland',
		'SE' => 'Sweden',
		'CH' => 'Switzerland',
		'SY' => 'Syrian Arab Republic',
		'TW' => 'Taiwan',
		'TJ' => 'Tajikistan',
		'TZ' => 'Tanzania',
		'TH' => 'Thailand',
		'TL' => 'Timor-Leste',
		'TG' => 'Togo',
		'TK' => 'Tokelau',
		'TO' => 'Tonga',
		'TT' => 'Trinidad And Tobago',
		'TN' => 'Tunisia',
		'TR' => 'Turkey',
		'TM' => 'Turkmenistan',
		'TC' => 'Turks And Caicos Islands',
		'TV' => 'Tuvalu',
		'UG' => 'Uganda',
		'UA' => 'Ukraine',
		'AE' => 'United Arab Emirates',
		'GB' => 'United Kingdom',
		'US' => 'United States',
		'UM' => 'United States Outlying Islands',
		'UY' => 'Uruguay',
		'UZ' => 'Uzbekistan',
		'VU' => 'Vanuatu',
		'VE' => 'Venezuela',
		'VN' => 'Viet Nam',
		'VG' => 'Virgin Islands, British',
		'VI' => 'Virgin Islands, U.S.',
		'WF' => 'Wallis And Futuna',
		'EH' => 'Western Sahara',
		'YE' => 'Yemen',
		'ZM' => 'Zambia',
		'ZW' => 'Zimbabwe',
	);
 }
 
 
  
 
 function wptuts_first_shortcode($atts, $content=null){
   
    //$post_url = get_permalink($post->ID);
    //$post_title = get_the_title($post->ID);
	
	$wg = new Webgalamb();
		
	if(isset($atts['id'])) $wg->defaultGroup = $atts['id'];
	
	if(isset($_POST['wg-sub'])) {
		if(!$wg->validateForm()) {
			foreach($wg->errorMessages as $er) echo '<div class="alert alert-primary" role="alert">'.$er.'</div>';
			$wg->formHtml();
		} else {
			$wg->sendSubscriptions();
		}
	} else {
		$wg->formHtml();
	}
	
	
    return true;
  }
   
  add_shortcode('webgalamb_feliratkozas', 'wptuts_first_shortcode');
   
  
  
  function isAssoc(array $arr)
{
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}
  
  
  