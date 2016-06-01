<?php

// Officiel version: v.0.1.3

/*********************************************************/
/**                                                     **/
/**                      guld.natu                      **/
/**      Tjek venligst GitHub for mere information      **/
/**                                                     **/
/*********************************************************/

/*
 * Foldager Media / Magnus Foldager ejer størstedelen af koden på denne side.
 * Koden som findes på denne side må ikke genanvendes uden skriftlig tilladelse fra Magnus Foldager,
 * eller anden medarbejder af Foldager Media.
 */

/* 
 * VÆR VENLIGST OPMÆRKSOM PÅ, AT MINE EGENSKABER I PHP ER BEGRÆNSEDE.
 * LAD GERNE VÆRE MED AT RÅBE AF MIG, BARE FORDI JEG ER DUM. ;)
 */

/*
 * Ting der skal overvejes ved udregning af nummerets værdi:
 * - Antal forskellige tal der indgår (f.eks. '21212121' indeholder kun 2 tal)
 * - Letlæseligheden af nummeret ('88880000' er nemmere at læse og huske end '88080080', selvom de samme tal indgår)
 * - Antal tal i rækkefølge (f.eks. 82701111 er mere værd end '82711011')
 * - Om der indgår kæder (f.eks. '87654321' eller '81234567')
 * - Om "dubs" går igen (f.eks. '35353535')
 * - Om "trips" går igen (f.eks. '31398313')
 * - Om det samme tal går igen hele vejen (f.eks. '88888888')
 * - Om nummeret ender på et godt tal (f.eks. 0 eller 5)
 * - Om tallet "tæller" (f.eks. 70121314 eller 51525354)
 */

/**
 * Kalder calculateValue() og sender det som echo til hovedsiden.
 */
echo calculateValue((int)str_replace(array(' ', ','), '', htmlspecialchars($_POST['form_number'])));

/**
 * calculateValue()
 * Benytter de andre funktioner til at udregne den omtrentlige pris
 * @param string $number - Nummeret som skaffes gennem $_POST['form_number']
 * @return string - Forslag på pris, "Uvurderligt" hvis højere end 500.000 DKK
 */
function calculateValue($number) {
	if(!preg_match('/^[0-9]{8}$/',$number)) {
		return "<small>Fejl. Tjek venligst telefonnummer.</small>";
		exit();
	} else {
		require_once('logging.php');
		$numbers = str_split((string)$number);
		$base = 117; //TODO: Find bedre start-tal. Denne værdi sætter et start-tal for algoritmen.
		$same = same($numbers);
		$chainValue = calculateChainValue((string)$number);
		$reverseChainValue = calculateReverseChainValue((string)$number);
		$goodEnding = goodEnding($numbers);
		$differentNumbers = differentNumbers($number);
		$zeroCount = zeroCount((string)$number);
		$dubs = checkDubs($numbers);
		$prisForslag = ($base + $same) * $chainValue * $reverseChainValue * $goodEnding * $differentNumbers * $zeroCount * $dubs;
		if($prisForslag >= 500000) {
			sendToLog($number,$prisForslag,'v.0.1.3'); // Denne linje er vigtig, da den logger al data for at forbedre algoritmen.
			return "Uvurderligt.";
			exit();
		} else {
			//formatering og return af den string, som bestemmer hvad der skal stå på siden.
			sendToLog($number,$prisForslag,'v.0.1.3'); // Denne linje er vigtig, da den logger al data for at forbedre algoritmen.
			return number_format($prisForslag,2,"<sup style=\"text-decoration:underline;\">",".")."</sup> DKK";
			exit();
		}
	}
}

/**
 * checkDubs()
 * Benytter de andre funktioner til at udregne den omtrentlige pris
 * @param array $numbers - Nummeret som skaffes gennem $_POST['form_number'] som opdelte tal gennem str_split
 * @return int - Tal at gange med til algoritmen
 */
function checkDubs($numbers) {
	$x = 1;
	$d1 = (string)$numbers[0] . (string)$numbers[1];
	$d2 = (string)$numbers[2] . (string)$numbers[3];
	$d3 = (string)$numbers[4] . (string)$numbers[5];
	$d4 = (string)$numbers[6] . (string)$numbers[7];
	if ($d1 == $d2 && $d2 == $d3 && $d3 == $d4) {
		$x=$x+250;
	}
	if ($d1 == $d2 && $d2 == $d3) {
		$x=$x+100;
	}
	if ($d2 == $d3 && $d3 == $d4) {
		$x=$x+150;
	}
	if ($d1 == $d2) {
		$x=$x+100;
	}
	if ($d2 == $d3) {
		$x=$x+100;
	}
	return $x;
}

/**
 * checkTrips()
 * Tjekker om der er trips eller ej
 * @param array $numbers - Nummeret som skaffes gennem $_POST['form_number'] som opdelte tal gennem str_split
 * @return int - Tal at gange med ift. trips eller ej
 */
function checkTrips($numbers) {
	//TODO: Tjek om der er "trips"
}

/**
 * differentNumbers()
 * Udregner hvor mange forskellige tal der er i nummeret der er
 * @param array $numbers - Nummeret som skaffes gennem $_POST['form_number'] som opdelte tal gennem str_split
 * @return int - Tal at gange med ift. hvor mange forskellige numre der er eller ej
 */
function differentNumbers($number) {
	$arr = array();
	for ($i=0; $i < 10; $i++) {
		$x = substr_count($number, $i);
		$arr["$i"] = $x;
	}
	$count = count(array_filter($arr));
	switch ($count) {
		case 8:
		return 0.8;
		break;
		case 7:
		return 0.9;
		break;
		case 6:
		return 1.2;
		break;
		case 5:
		return 1.8;
		break;
		case 4:
		return 2.7;
		break;
		case 3:
		return 3.2; // Bliver lavere igen herefter, da numre med kun 2 eller 1 tal bliver højere gennem de andre funktioner alligevel
		break;
		case 2:
		return 2.1;
		break;
		case 1:
		return 1.2;
		break;
		default:
		return 0.8;
		break;
	}
}

/**
 * zeroCount()
 * Tæller antal nuller
 * @param string $number - Nummeret som skaffes gennem $_POST['form_number']
 * @return int - Tal at gange med ift. hvor mange forskellige nuller der er. Altid ≥ 1, for at undgå at tal uden 0 == 0
 */
function zeroCount($number) {
	$x = substr_count($number, '0');
	return $x + 1;
}

/**
 * calculateChainValue()
 * Udregner værdi af kæder/sekvenser
 * @param string $number - Nummeret som skaffes gennem $_POST['form_number']
 * @return int - Tal at gange med ift. hvor stor en kæde der findes. Altid ≥ 1, for at undgå at tal uden kæde == 0
 */
function calculateChainValue($number) {
	if(containsChain($number,8)) {
		return 1399;
	} else if(containsChain($number,7)) {
		return 890;
	} else if(containsChain($number,6)) {
		return 279;
	} else if(containsChain($number,5)) {
		return 122;
	} else if(containsChain($number,4)) {
		return 18;
	} else if(containsChain($number,3)) {
		return 3;
	} else if(containsChain($number,2)) {
		return 2;
	} else if(containsChain($number,1)) {
		return 1;
	} else return 1;
}

/**
 * calculateReverseChainValue()
 * Udregner værdi af omvendte kæder/sekvenser. Lavere end calculateChainValue(), idet 123 er mere værd end 321
 * @param string $number - Nummeret som skaffes gennem $_POST['form_number']
 * @return bool - Tal at gange med ift. hvor stor en omvendt kæde der findes. Altid ≥ 1, for at undgå at tal uden omvendt kæde == 0
 */
function calculateReverseChainValue($number) {
	if(containsReverseChain($number,8)) {
		return 1299;
	} else if(containsReverseChain($number,7)) {
		return 820;
	} else if(containsReverseChain($number,6)) {
		return 230;
	} else if(containsReverseChain($number,5)) {
		return 113;
	} else if(containsReverseChain($number,4)) {
		return 16;
	} else if(containsReverseChain($number,3)) {
		return 3;
	} else if(containsReverseChain($number,2)) {
		return 2;
	} else if(containsReverseChain($number,1)) {
		return 1;
	} else return 1;
}

/**
 * containsChain()
 * Find ud af om nummeret indeholder en kæde/sekvens af numre
 * @param string $number - Nummeret som skaffes gennem $_POST['form_number']
 * @param int $n_chained_expected - Forventet længde på kæde - alle længder afprøves alligevel, såååh..
 * @return bool - Boolean ift. om der eksisterer en kæde af x længde
 */
function containsChain($number,$n_chained_expected) {
	$chained = 1;
	for($i=1; $i<strlen($number); $i++)
	{
		if($number[$i] == ($number[$i-1] + 1))
		{
			$chained++;
			if($chained >= $n_chained_expected)
				return true;
		} else{
			$chained = 1;
		}
	}
	return false;
}


/**
 * containsReverseChain()
 * Find ud af om nummeret indeholder en omvendt kæde/sekvens af numre
 * @param string $number - Nummeret som skaffes gennem $_POST['form_number']
 * @param int $n_chained_expected - Forventet længde på omvendt kæde - alle længder afprøves alligevel, såååh..
 * @return bool - Boolean ift. om der eksisterer en omvendt kæde af x længde
 */
function containsReverseChain($number,$n_chained_expected) {
	$new = strrev($number);
	$chained = 1;
	for($i=1; $i<strlen($new); $i++)
	{
		if($new[$i] == ($new[$i-1] + 1))
		{
			$chained++;
			if($chained >= $n_chained_expected)
				return true;
		} else{
			$chained = 1;
		}
	}
	return false;
}

/**
 * same()
 * Find ud af om nummeret indeholder samme tal i flere felter. Denne funktion er ikke køn.
 * @param array $numbers - Nummeret som skaffes gennem $_POST['form_number'] som opdelte tal gennem str_split
 * @return int - Start-prisklasse baseret på hvor mange af det samme nummer der er.....
 */
function same($numbers) { //TODO: Gør denne funktion pænere D:
	$pk = 0;
	if ($numbers[0] == $numbers[1]) {
		$pk++;
	}
	if ($numbers[1] == $numbers[2]) {
		$pk++;
	}
	if ($numbers[2] == $numbers[3]) {
		$pk++;
	}
	if ($numbers[3] == $numbers[4]) {
		$pk++;
	}
	if ($numbers[4] == $numbers[5]) {
		$pk++;
	}
	if ($numbers[5] == $numbers[6]) {
		$pk++;
	}
	if ($numbers[6] == $numbers[7]) {
		$pk++;
	}
	switch ($pk) {
		case 0:
		$r = 8;
		break;
		case 1:
		$r = 17;
		break;
		case 2:
		$r = 213;
		break;
		case 3:
		$r = 409;
		break;
		case 4:
		$r = 4090;
		break;
		case 5:
		$r = 14808;
		break;
		case 6:
		$r = 151782;
		break;
		case 7:
		$r = 1522756;
		break;
		default:
		$r = 10;
		break;
	}
	return $r;
}

/**
 * containsReverseChain()
 * Find ud af om nummeret indeholder samme tal i flere felter. Denne funktion er ikke køn.
 * @param array $numbers - Nummeret som skaffes gennem $_POST['form_number'] som opdelte tal gennem str_split
 * @return int - Start-prisklasse baseret på hvor mange af det samme nummer der er.....
 */
function goodEnding($numbers) {
	if ($numbers[7] == 0) {
		return 1.4;
	} else if ($numbers[7] == 5) {
		return 1.1;
	}
	else return 1.0;
}

?>
