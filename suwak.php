<!DOCTYPE HTML> 
<html>
<head>
<style>
</style>
</head>
<body> 

<?php
if ( !current_user_can( 'manage_options' ) )  {
	wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
}

$staff = test_input($_POST["staff"]);
$suwak= test_input($_POST["suwak"]);
$pytanie= test_input($_POST["pytanie"]);

function test_input($data) {
   $data = trim($data);
   $data = stripslashes($data);
   $data = htmlspecialchars($data);
   return $data;
}
?>

<h2>Głosowanie typu "suwakowego"</h2>

<form method="post" >

   <br><br>
   Pytanie:<br> <input type="text" name="pytanie" size="80" value="">

   <br><br>

   Długość suwaka: <input type="text" name="suwak" size="10" value="">
   
   <br><br>

   <br>Dla kogo ma być przeznaczone głosowanie?
   <br><input type="radio" name="staff" <?php if (isset($staff) && $staff=="0") echo "checked"; ?> value="2">Tylko studenci
   <br><input type="radio" name="staff" <?php if (isset($staff) && $staff=="1") echo "checked"; ?> value="1">Tylko nauczyciele
   <br><input type="radio" name="staff" <?php if (isset($staff) && $staff=="2") echo "checked"; ?> value="-1">Wszyscy


   <br><br>
   Czy głosowanie ma mieć termin ważności?
   Jeśli nie pozostaw puste
   <br>W przeciwnym wypadku wpisz termin ważności w formacie RRRR-MM-DD GG-MM-SS
   <br><input type="datetime-local" name="czas">
   <br><br>
   Czy chcesz aby możnabyło obejrzeć wyniki głosowania?
   <br><input type="radio" name="wyniki" value="1">Tak
   <br><input type="radio" name="wyniki" value="2">Nie
   <br><br>
   <input type="submit" name="submit" value="Stwórz głosowanie"> 
</form>


<?php

global $wpdb;
$current_user = wp_get_current_user();
$ajdi = $wpdb->get_col( $wpdb->prepare( "SELECT id_osoby FROM Osoba WHERE imie='$current_user->user_firstname' AND nazwisko='$current_user->user_lastname'" ) );
$id_us = $ajdi[0];

if (!empty($pytanie) and isset($pytanie) and !empty($staff) and isset($staff)  and isset($_POST['wyniki']) and !empty($_POST['wyniki'])) {
	echo "<h2> Twoje pytanie do głosowanie było następujące: </h2>";
	echo $pytanie;
	echo "<br>";
	echo "<h2> Długość suwaka dla Twojego pytania była następująca: </h2>";
	echo $suwak;
	echo "<br>";
	if ($staff == 1)
		echo "<br>Głosowanie tylko dla nauczycieli";
	if ($staff == 2)
		echo "<br>Głosowanie tylko dla studentów";
	if ($staff == -1)
		echo "<br>Głosowanie dla wszystkich";
	if (isset($_POST['czas']) and !empty($_POST['czas'])){
		$czass = $_POST['czas'];
		echo "<br>Głosowanie ważne do $czass";
	}	
	$wpdb->insert(
		'Pytanie',
		array(
			pytanie => $pytanie,
			typ => 5,
			id_tworcy => $id_us
		),
		array(
			'%s',
			'%d',
			'%d'
		)
	);
	$id_pyt = $wpdb->get_col( $wpdb->prepare("SELECT id_pytania FROM Pytanie WHERE pytanie='$pytanie' ORDER BY id_pytania DESC") );
	$wpdb->insert(
		'PytanieSuwakowe',
		array(
			id_pytania => $id_pyt[0],
			n => $suwak,
		),
		array(
			'%d',
			'%d'
		)
	);
	$wpdb->insert(
		'Warunek',
		array(
			id_pytania => $id_pyt[0],
			warunek => $staff,
		),
		array(
			'%d',
			'%s'
		)
	);
	$data;
	if (isset($_POST['czas']) and !empty($_POST['czas'])){
		$data = $_POST['czas'];
	} else {
		$data = "2030-12-01 00:00:00";
	}
	$wyniki = $_POST['wyniki'];
	$wpdb->insert(
		'Termin',
		array(
			id_pytania => $id_pyt[0],
			termin => $data,
			aktywne => 1,
			wyniki => $wyniki
		)
	);
	echo "<h3> Twoje głosowanie dodane pomyślnie :)</h3>";
}

?>

</body>
</html>
