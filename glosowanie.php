<?php 
/**
 * Plugin Name: Glosowanie
 *
 */

add_shortcode('wyniki_glosowan', 'wyswietl_wyniki');
function wyswietl_wyniki( $atts ){
if ( !current_user_can( 'manage_options' ) )  {
	wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
}

	global $wpdb;
	$current_user = wp_get_current_user();
	$ajdi = $wpdb->get_col( $wpdb->prepare( "SELECT id_osoby FROM Osoba WHERE imie='$current_user->user_firstname' AND nazwisko='$current_user->user_lastname'") );
	$id_os = $ajdi[0];
	##sprawdzam czy dopiero wybieram pytania czy juz wybralem badz oddalem glos
	if ( !isset($_POST['submit']) and empty($_POST['submit']) ){
		echo "<h1>Wybierz głosowanie, które chcesz obejrzeć</h1>";
		$pytania_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id_pytania FROM Pytanie"));
		$status_os = $wpdb->get_col( $wpdb->prepare( "SELECT nr_indeksu FROM Osoba WHERE id_osoby=$id_os" ) );
		?> <form method=post ><select name="pytanie"> <?php	
			
		foreach($pytania_ids as $key){
			## warunek czy osoba moze wziasc udzial w tym glosowaniu
			$pyt = $wpdb->get_col ( $wpdb->prepare( "SELECT pytanie FROM Pytanie WHERE id_pytania=$key" ) );
			## pobieram warunek
			$war = $wpdb->get_col( $wpdb->prepare( "SELECT warunek FROM Warunek WHERE id_pytania=$key" ) );
			$termin = $wpdb->get_col( $wpdb->prepare( "SELECT termin FROM Termin WHERE id_pytania=$key" ) );
			$aktywne = $wpdb->get_col( $wpdb->prepare( "SELECT aktywne FROM Termin WHERE id_pytania=$key" ) );
			$wyniki = $wpdb->get_col( $wpdb->prepare( "SELECT wyniki FROM Termin WHERE id_pytania=$key" ) );
			$nie_moze;
			if ( $status_os[0] == 0 ){
				$nie_moze = 1;
			} else {
				## jest pracownikiem
				$nie_moze = 2;
			}
			
			if ( !($war[0] == $nie_moze ) and ( ($wyniki[0] == 1) or ( $wyniki[0] == 2 and ( $aktywne[0] == 0 or (strtotime($termin[0]) < strtotime(date("Y-m-d H-i-s", time() + 3600)))  )  )   ) ) {
				?>
					<option value="<?php echo $key; ?>"><?php echo $pyt[0]; ?></option>
				<?php 
			}
		}
		?>
		</select>
			<br><br><input type="submit" name="submit" value="Pokaż to głosowanie">				
		</form>
		<?php
	} else {
		global $wpdb;
		## wyswietlam wyniki do pytania #_POST['pytanie']
		## GŁOS ODDANY
		## CZYLI MIEJSCE NA WRZUCENIE WSZYTKIEGO DO DB + WYSWIETLENIE WYNIKU GLOSOWANIA 
		$pom = $_POST['pytanie'];
		$typ = $wpdb->get_col( $wpdb->prepare( "SELECT typ FROM Pytanie WHERE id_pytania=$pom" ) );
		$liczba_wszystkich;
		if (($typ[0] > 0) and ($typ[0] < 3)){
			$liczba_wszystkich = $wpdb->get_col( $wpdb->prepare("SELECT count(*) FROM GlosyWyboru WHERE id_pytania=$pom GROUP BY id_pytania")  );
		} else {
			$liczba_wszystkich = $wpdb->get_col( $wpdb->prepare("SELECT count(*) FROM GlosSuwakowy WHERE id_pytania=$pom GROUP BY id_pytania")  );
		}
		## mam liczbe wszytkich glosow oddanych w tym pytaniu
		## tresc pytania
		$pytaniee = $wpdb->get_col( $wpdb->prepare( "SELECT pytanie FROM Pytanie WHERE id_pytania=$pom" ) );
		echo "<h2> $pytaniee[0] </h2>";
		echo "<h5> Wyniki: </h5>";
		if ((intval($typ[0]) == 1) or (intval($typ[0]) == 2)) {
			## glosowanie wyboru
			$odpowiedzi_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id_odpowiedzi FROM Odpowiedzi WHERE id_pytania=$pom" ) );
			foreach($odpowiedzi_ids as $odp){
				$tresc = $wpdb->get_col( $wpdb->prepare ( "SELECT tresc FROM Odpowiedzi WHERE id_odpowiedzi=$odp") );
				$ile = $wpdb->get_col( $wpdb->prepare( "SELECT count(*) FROM GlosyWyboru  WHERE id_odpowiedzi=$odp GROUP BY id_odpowiedzi") );
				$proc = bcdiv($ile[0], $liczba_wszystkich[0], 4);
				$proc = $proc * 100;
				echo"<br> $tresc[0] ".$proc."%";
			}
		}
		if (intval($typ[0]) == 5){
			## glosowanie suwakowe
			$srednia = $wpdb->get_col( $wpdb->prepare( "SELECT avg(wartosc) FROM GlosSuwakowy WHERE id_pytania=$pom GROUP BY id_pytania"  ) );
			echo "Średnia odpowiedzi na to pytanie wynosi ".$srednia[0];
		}
		if ( intval( $typ[0]) == 3 ) {
			## lubieeee toooo
			$lubieto = $wpdb->get_col ( $wpdb->prepare( "SELECT count(*) FROM GlosSuwakowy WHERE id_pytania=$pom and wartosc=1 GROUP BY id_pytania" ) );
			echo "<br> Liczba osób, które już to polubiły to ".$lubieto[0]."!"." Łączna liczba osób głosujących to ".$liczba_wszystkich[0].".";
		}
		if (intval( $typ[0]) == 4 ){
			## gwiazdky
			$gwiazdki = $wpdb->get_col ( $wpdb->prepare( "SELECT avg(wartosc) FROM GlosSuwakowy WHERE id_pytania=$pom GROUP BY id_pytania" ) );
			$wys = array(
					rating => $gwiazdki[0],
					type => rating,
					number => $liczba_wszystkich[0],
			);
			$i = 1;
			for($i = 1; $i <= $gwiazdki[0]; $i++){
				?>	
				<img src="wp-content/plugins/glosowanie/rate1.png">					
				<?php					
			}				
			if($gwiazdki[0] - $i > -1 ){
				?>
					<img src="wp-content/plugins/glosowanie/rate.png">						
				<?php
				$i = $i + 1;
			}				
			for( $i = $i; $i <= 5; $i++){
				?>
				<img src="wp-content/plugins/glosowanie/rate0.png">
				<?php
			}
			echo "<p><small> Średnia $gwiazdki[0] z $liczba_wszystkich[0] głosów.</small></p>";
			
		}
        	 
			
		
	}

}



add_shortcode('glosowania', 'wyswietl_glosowania');
function wyswietl_glosowania( $atts ){
if (is_user_logged_in()){
	global $wpdb;
	$current_user = wp_get_current_user();
	$ajdi = $wpdb->get_col( $wpdb->prepare( "SELECT id_osoby FROM Osoba WHERE imie='$current_user->user_firstname' AND nazwisko='$current_user->user_lastname'") );
	$id_os = $ajdi[0];
	session_start();
	##sprawdzam czy dopiero wybieram pytania czy juz wybralem badz oddalem glos
	if ((!isset($_POST['submit']) and empty($_POST['submit'])) and !isset($_POST['glosuj']) and empty($_POST['glosuj']) and !isset($_POST['wyniki']) and empty($_POST['wyniki']) and !isset($_POST['cofnijglos']) and empty($_POST['cofnijglos']) ){
		echo "<h1>Wybierz głosowanie, w którym chcesz uczestniczyć</h1>";
		$pytania_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id_pytania FROM Pytanie"));
		$status_os = $wpdb->get_col( $wpdb->prepare( "SELECT nr_indeksu FROM Osoba WHERE id_osoby=$id_os" ) );
				?> <form method=post ><select name="pytanie"> <?php	
			
		foreach($pytania_ids as $key){
			## warunek czy osoba moze wziasc udzial w tym glosowaniu
			$pytanie = $wpdb->get_col ( $wpdb->prepare( "SELECT pytanie FROM Pytanie WHERE id_pytania=$key" ) );
			## pobieram warunek
			$war = $wpdb->get_col( $wpdb->prepare( "SELECT warunek FROM Warunek WHERE id_pytania=$key" ) );
			$termin = $wpdb->get_col( $wpdb->prepare( "SELECT termin FROM Termin WHERE id_pytania=$key" ) );
			$aktywne = $wpdb->get_col( $wpdb->prepare( "SELECT aktywne FROM Termin WHERE id_pytania=$key" ) );
			if (  ( (intval($stauts_os[0]) == 0 and !(intval($war[0]) == 1)) or (intval($status_os[0]) == 1 and !(intval($war[0]) == 2) ) )   and (intval($aktywne[0]) == 1) and ( (strtotime($termin[0]) ) > strtotime( date("Y-m-d H:i:s", time() + 3600) ) ) ) {
			
				?>
					<option value="<?php echo $key; ?>"><?php echo $pytanie[0] ?></option>
				<?php 
			}
		}
		?>
		</select>
			<br><br><input type="submit" name="submit" value="Pokaż to głosowanie">				
		</form>
<?php 
	} else {
		## WYŚWIETLAM GŁOSOWANIE
		$pyt_id = $_POST['pytanie'];
		$pytar = $wpdb->get_col( $wpdb->prepare( "SELECT pytanie FROM Pytanie WHERE id_pytania=$pytanie_id") );
		$pyt = $pytar[0];
		## czy oddalem glos
		if (!isset($_POST['glosuj']) and empty($_POST['glosuj']) and !isset($_POST['wyniki']) and empty($_POST['wyniki']) and !isset($_POST['cofnijglos']) and empty($_POST['cofnijglos']) ){
			## oddaje głos
			$method = ""; 
			$numer = $wpdb->get_col( $wpdb->prepare( "SELECT typ FROM Pytanie WHERE id_pytania=$pyt_id" ));
			$tmp;
			switch( $numer[0] ){
				case '1' :
					## 1wyboru 
					$tmp = 1;
					$method = 'radio';
					break;
				case '2' :
					## wwyboru 
					$tmp = 2;
					$method = 'checkbox';
					break;
				case '3' :
					## lubie to
					$tmp = 3;
					$method = 'radio';
					break;
				case '4' :
					## gwiazki
					$tmp = 4;
					$method = 'gwiazdki';
					break;
				case '5' :
					## suwak
					$tmp = 5;
					$method = 'suwak';
					break;
			}
			$_SESSION['tmp'] = $tmp;
			$_SESSION['met'] = $method;
			$_SESSION['id'] = $pyt_id;
			$pid = $_POST['pytanie'];
			$tresc = $wpdb->get_col( $wpdb->prepare( "SELECT pytanie FROM Pytanie WHERE id_pytania=$pid" ) );
			## wyswietlam pytanie
			?> <h2> <?php $tresc ?> </h2> <?php
	
			## petla po odpowiedziach 
			if ($method == radio or $method == checkbox){			
				if ( intval($numer[0]) == '3'){
					## GŁOSOWANIE LUBIE TO 
					?>
					<form method=post>
						<input type="radio" name="mojglos" value=1> Lubię to!<br>
						<input type="radio" name="mojglos" value=0> Nie lubię tego!<br>
						<br><input type="submit" name="glosuj" value="oddaj glos">
						<br><br><input type="submit" name="wyniki" value="Pokaż wyniki">
					</form>
					<?php
				} elseif ( intval($tmp) == 2){
					## GŁOSOWANIE WIELOKROTNEGO WYBORU 
					$odpowiedzi = $wpdb->get_col( $wpdb->prepare( "SELECT id_odpowiedzi FROM Odpowiedzi WHERE id_pytania=$pyt_id" ) );
					?>
						<form method=post>
					<?php
					foreach($odpowiedzi as $id){
						$odp = $wpdb->get_col( $wpdb->prepare( "SELECT tresc FROM Odpowiedzi WHERE tresc='$odp' AND id_pytania=$pyt_id" ) );
						?>
							<input type="checkbox" name="mojglos[]" value="<?php echo $id ?>"><?php echo $odp[0]; ?><br>
						<?php
					}
					?>
						<br><input type="submit" name="glosuj" value="oddaj glos">
						<br><br><input type="submit" name="wyniki" value="Pokaż wyniki">
						</form>
					<?php
				} elseif ( intval($tmp) == 1){
					## GŁOSOWANIE JEDNOKROTNEGO WYBORU
					$odpowiedzi = $wpdb->get_col( $wpdb->prepare ( "SELECT id_odpowiedzi FROM Odpowiedzi WHERE id_pytania=$pyt_id" ) );
					?>
						<form method=post>
					<?php
					foreach($odpowiedzi as $id){
						$odp = $wpdb->get_col( $wpdb->prepare ( "SELECT tresc FROM Odpowiedzi WHERE id_odpowiedzi=$id AND id_pytania=$pyt_id" ) );
						?>
							<input type="radio" name="mojglos" value="<?php echo $id; ?>"><?php echo $odp[0]; ?><br>
						<?php
					}
					?>
						<br><input type="submit" name="glosuj" value="Oddaj głos">
						<br><br><input type="submit" name="wyniki" value="Pokaż wyniki">
						</form>
					<?php
				}
			} else {
				## GLOSOWANIE SUWAKOWE
				if ($method == 'suwak' and intval($tmp) == 5){
					## mam suwak
					$dl_suwaka = $wpdb->get_col ( $wpdb->prepare ( "SELECT n FROM PytanieSuwakowe WHERE id_pytania=$pyt_id" ) );
					
					?>
						<form method=post>
							<input type="range" name='mojglos' min="0" max="<?php echo $dl_suwaka[0]; ?>" value="0" step="1" onchange="showValue(this.value)" size=100 /></br>
							<span id="range">0</span>
							<br><br><input type="submit" name="glosuj" value="Oddaj głos">
							<br><br><input type="submit" name="wyniki" value="Pokaż wyniki">
						</form>
						<script type="text/javascript">
						function showValue(newValue)
						{
							document.getElementById("range").innerHTML=newValue;
						}
						</script>
					<?php
				} elseif ( $method == 'gwiazdki' and intval($tmp) == 4)  {
					##gwiazdki
					?>
						<form method=post>
							<input type="radio" name='mojglos' value=1>
							<input type="radio" name='mojglos' value=2>
							<input type="radio" name='mojglos' value=3>
							<input type="radio" name='mojglos' value=4>
							<input type="radio" name='mojglos' value=5>
							<h3>&#9734   &#9734   &#9734   &#9734   &#9734</h3></br>
							<br><br><input type="submit" name='glosuj' value='Oddaj glos'></br>
							<br><br><input type="submit" name="wyniki" value="Pokaż wyniki">
						</form>
					<?php
				}
			}
		
		
		} elseif ( !isset($_POST['cofnijglos']) and empty($_POST['cofnijglos'])  ) {
			global $wpdb;
			## GŁOS ODDANY
			## CZYLI MIEJSCE NA WRZUCENIE WSZYTKIEGO DO DB + WYSWIETLENIE WYNIKU GLOSOWANIA 
			$zmienna = $_POST['mojglos'];
			$tablica;
			$current_user = wp_get_current_user();
			$ajdi = $wpdb->get_col( $wpdb->prepare( "SELECT id_osoby FROM Osoba WHERE imie='$current_user->user_firstname' AND nazwisko='$current_user->user_lastname'"  ) );
			$id_os = $ajdi[0];
			if (is_array($zmienna) == 'Array()'){
				$tablica = true;
			}else{

				$tablica = false;
			}
			$_SESSION['odp'] = $_POST['mojglos'];	
			## do tabeli Głosy wyboru
			if ($_SESSION['tmp'] == 1){
				if (!$tablica){
					if (!($zmienna == '')) {
						$wpdb->insert(
							'GlosyWyboru',
							array(
								id_osoby => $id_os,
								id_pytania => $_SESSION['id'],
								id_odpowiedzi => $zmienna
							),
							array(
								'%d',
								'%d',
								'%d'
							)
						);
					}
				}
			}  elseif($_SESSION['tmp'] == 2) {
					foreach($zmienna as $key){
						if (!($key == '')){
							$wpdb->insert (
								'GlosyWyboru',
								array(
									id_osoby => $id_os,
									id_pytania => $_SESSION['id'],
									id_odpowiedzi => $key
								),
								array(
									'%d',
									'%d',
									'%d'
								)
							);
						}
					}
			}  elseif ($_SESSION['tmp'] == 3){
				## lubie to
				if (!($zmienna == '')) {
					$wpdb->insert(
						'GlosSuwakowy',
						array(
							id_osoby => $id_os,
							id_pytania => $_SESSION['id'],
							wartosc => $zmienna
						),
						array(
							'%d',
							'%d',
							'%d'
						)
					);
				}
			} elseif ($_SESSION['tmp'] == 5) {
				## suwak
				if (!($zmienna == '')){
					$wpdb->insert(
						'GlosSuwakowy',
						array(
							id_osoby => $id_os,
							id_pytania => $_SESSION['id'],
							wartosc => $zmienna
						),
						array(
							'%d',
							'%d',
							'%d'
						)
					);
				}
			} elseif ($_SESSION['tmp'] == 4) {
				## gwiazdkiiiiii
				if ( !($zmienna == '') ) {
					$wpdb->insert(
						'GlosSuwakowy',
						array(
							id_osoby => $id_os,
							id_pytania => $_SESSION['id'],
							wartosc => $zmienna
						),
						array(
							'%d',
							'%d',
							'%d'
						)
					);
				}
			}
			
			## wysietlanie wynikow
			$skad;
			if ($_SESSION['tmp'] <3){
				$skad = 'GlosyWyboru';
			} else {
				$skad = 'GlosSuwakowy';
			}
			$pom = $_SESSION['id'];
			$czy_pokazac = $wpdb->get_col( $wpdb->prepare( "SELECT wyniki FROM Termin WHERE id_pytania=$pom") );


                	if (intval($czy_pokazac[0]) == 1) {

				## mam liczbe wszytkich glosow oddanych w tym pytaniu
				$liczba_wszystkich = $wpdb->get_col( $wpdb->prepare("SELECT count(*) FROM $skad WHERE id_pytania=$pom GROUP BY id_pytania")  );

				## tresc pytania
				$pytaniee = $wpdb->get_col( $wpdb->prepare( "SELECT pytanie FROM Pytanie WHERE id_pytania=$pom" ) );
				echo "<h2> $pytaniee[0] </h2>";
				echo "<h5> Wyniki: </h5> ";	
				if ($skad == 'GlosyWyboru') {
					## glosowanie wyboru
					$odpowiedzi = $wpdb->get_col( $wpdb->prepare( "SELECT tresc FROM Odpowiedzi WHERE id_pytania=$pom" ) );
					foreach($odpowiedzi as $odp){
						$id_odp = $wpdb->get_col( $wpdb->prepare ( "SELECT id_odpowiedzi FROM Odpowiedzi WHERE tresc='$odp' AND id_pytania=$pom") );
						$ile = $wpdb->get_col( $wpdb->prepare( "SELECT count(*) FROM GlosyWyboru  WHERE id_odpowiedzi=$id_odp[0] GROUP BY id_odpowiedzi") );

						$proc = bcdiv($ile[0], $liczba_wszystkich[0], 4);
						$proc = $proc * 100;
						echo"<br> $odp ".$proc."%";
					}
				}
				if ($skad == 'GlosSuwakowy' and $_SESSION['tmp'] == 5){
					## glosowanie suwakowe
					$srednia = $wpdb->get_col( $wpdb->prepare( "SELECT avg(wartosc) FROM GlosSuwakowy WHERE id_pytania=$pom GROUP BY id_pytania"  ) );
					echo "Średnia odpowiedzi na to pytanie wynosi ".$srednia[0];
				}
				if ( $skad == 'GlosSuwakowy' and $_SESSION['tmp'] == 3 ) {
					## lubieeee toooo
					$lubieto = $wpdb->get_col ( $wpdb->prepare( "SELECT count(*) FROM GlosSuwakowy WHERE id_pytania=$pom and wartosc=1 GROUP BY id_pytania" ) );
					echo "<br> Liczba osób, które już to polubiły to ".$lubieto[0]."!"." Łączna liczba osób głosujących to ".$liczba_wszystkich[0].".";
				}
				if ($skad == 'GlosSuwakowy' and $_SESSION['tmp'] == 4 ){
					## gwiazdky
					$gwiazdki = $wpdb->get_col ( $wpdb->prepare( "SELECT avg(wartosc) FROM GlosSuwakowy WHERE id_pytania=$pom GROUP BY id_pytania" ) );
					$wys = array(
						rating => $gwiazdki[0],
						type => rating,
						number => $liczba_wszystkich[0],
					);

						$i = 1;
						for($i = 1; $i <= $gwiazdki[0]; $i++){
					?>	
						<img src="wp-content/plugins/glosowanie/rate1.png">					

					<?php					
						}
					
					if($gwiazdki[0] - $i > -1 ){
						?>
							<img src="wp-content/plugins/glosowanie/rate.png">						
						<?php
						$i = $i + 1;
					}
				
					for( $i = $i; $i <= 5; $i++){
						?>
							<img src="wp-content/plugins/glosowanie/rate0.png">
						<?php
					}
					echo "<p><small> Średnia $gwiazdki[0] z $liczba_wszystkich[0] głosów.</small></p>";
				
				}		
				?>
					<form method=post>
							<br><br><input type="submit" name="cofnijglos" value="Cofnij głos!">
					</form>
				<?php	
        	 	} else  ##nie pokazuj wyniku
	 			echo "<h1>Niestety wyniki dostępne są tylko dla administratorów</h1>";
			
		
		} else {
			## COFNIJ WYNIKKKK
			echo "COFNIJWYNIIK".$_SESSION['tmp']."idP".$_SESSION['id'][0]."odp".$_SESSION['odp'];
			echo "<h1>Twój głos został cofnięty!</h1>";
			
			if ( $_SESSION['tmp'] > 2 ) {
				## glos suwakowy
				$id_pyt = $_SESSION['id'];
				$odp = $_SESSION['odp'];
				$id_gl = $wpdb->get_col( $wpdb->prepare( "SELECT id_glosu FROM GlosSuwakowy WHERE id_osoby=$id_os AND id_pytania=$id_pyt AND wartosc=$odp" ) );
				$wpdb->delete( 'GlosSuwakowy', array( id_glosu => $id_gl[0]), array( '%d' )  );
			}  elseif ( ($_SESSION['tmp'] < 3) and ($_SESSION['tmp'] > 0)  ) {
				## glos wyboru
				$id_pyt = $_SESSION['id'];
				if ( is_array($_SESSION['odp'] ) ){
					## wielokrotnego wyboru
					foreach ($_SESSION['odp'] as $key){
						$id_gl = $wpdb->get_col( $wpdb->prepare( "SELECT id_glosu FROM GlosyWyboru WHERE id_osoby=$id_os AND id_pytania=$id_pyt AND wartosc=$key" ) );
						$wpdb->delete( 'GlosyWyboru', array( id_glosu => $id_gl[0] ), array( '%d' ) );
					}
				} else {
					$odp = $_SESSION['odp'];
					$id_gl = $wpdb->get_col( $wpdb->prepare( "SELECT id_glosu FROM GlosyWyboru WHERE id_osoby=$id_os AND id_pytania=$id_pyt AND wartosc=$odp" ) );
					$wpdb->delete( 'GlosyWyboru', array( id_glosu => $id_gl[0] ), array( '%d' ) );
				}
			}
		}
	
	}

	
	
	

} else {
	echo " Musisz się zalogować aby przeglądać głosowania";
}

}


/** Tworzenie menu admina */
add_action( 'admin_menu', 'my_plugin_menu' );

/** Step 1.
  * TWORZENIE MENU I PODSTRON MENU
 */
function my_plugin_menu() {
	add_menu_page( 'Dodaj głosowanie', 'Dodaj głosowanie', 'administrator', 'glosowanie/wyboru.php');
	add_submenu_page( 'glosowanie/wyboru.php', 'Dodaj głosowanie wyboru', 'Dodaj głosowanie wyboru',  'administrator' , 'glosowanie/wyboru.php');
	add_submenu_page( 'glosowanie/wyboru.php', 'Dodaj głosowanie typu "Lubię to!"' , 'Dodaj głosowanie typu "Lubię to!"', 'administrator', 'glosowanie/lubieto.php');
	add_submenu_page( 'glosowanie/wyboru.php', 'Dodaj głosowanie typu "gwiazdkowego"', 'Dodaj głosowanie typu "gwiazdkowego"', 'administrator', 'glosowanie/gwiazdki.php');
	add_submenu_page( 'glosowanie/wyboru.php', 'Dodaj głosowanie typu "suwakowego"', 'Dodaj głosowanie typu "suwakowego"', 'administrator', 'glosowanie/suwak.php');
	add_submenu_page( 'glosowanie/wyboru.php', 'Panel do modyfikacji głosowań', 'Panel do modfikacji głosowań', 'administrator', 'glosowanie/modyfikacja.php');
	add_submenu_page( 'glosowanie/wyboru.php', 'Wyniki', 'Wyniki', 'administrator', 'glosowanie/wyniki.php');
}

/** Inicjacja MySQL DB */
register_activation_hook( __FILE__, 'gl_inicjacja_bd' );


global $gl_db_version;
$gl_db_version = "1.0";

function gl_inicjacja_bd(){
	global $wpdb;
	global $gl_db_version;

	#$table_name = $wpdb->prefix.'GlosSuwakowy';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE " .$table_name . " (
		id_glosu int NOT NULL AUTO_INCREMENT ,
		id_osoby int NOT NULL ,
    		id_pytania int NOT NULL ,
    		wartosc int NOT NULL ,
    		CONSTRAINT GlosSuwakowy_pk PRIMARY KEY  (id_glosu)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	/* 2 tabela */
	#$table_name = $wpdb->prefix.'GlosyWyboru';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE " .$table_name . " (
		id_glosu int NOT NULL AUTO_INCREMENT ,
		id_osoby int NOT NULL ,
    		id_pytania int NOT NULL ,
    		id_odpowiedzi int NOT NULL ,
    		CONSTRAINT GlosyWyboru_pk PRIMARY KEY  (id_osoby,id_pytania,id_odpowiedzi)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	/* 3 tabela */
	#$table_name = $wpdb->prefix.'Odpowiedzi';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE " .$table_name . " (
		id_odpowiedzi int NOT NULL ,
    		id_pytania int NOT NULL ,
   		tresc varchar(255) NOT NULL ,
    		CONSTRAINT Odpowiedzi_pk PRIMARY KE Y (id_odpowiedzi)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	/* 4 tabela */
	#$table_name = $wpdb->prefix.'Osoba';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE " .$table_name . " (
		id_osoby int NOT NULL AUTO_INCREMENT ,
    		imie varchar(50) NOT NULL ,
    		nazwisko varchar(50) NOT NULL ,
    		nr_indeksu int NULL ,
    		CONSTRAINT Osoba_pk PRIMARY KEY  (id_osoby)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	/* 5 tabela */
	#$table_name = $wpdb->prefix.'Pytanie';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE " .$table_name . " (
		id_pytania int NOT NULL AUTO_INCREMENT ,
    		pytanie varchar(255) NOT NULL ,
    		typ int NOT NULL ,
    		id_tworcy int NOT NULL ,
		wynik int NOT NULL ,
    		CONSTRAINT Pytanie_pk PRIMARY KEY  (id_pytania)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	/* 6 tabela */
	#$table_name = $wpdb->prefix.'PytanieSuwakowe';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE " .$table_name . " (
		id_pytania int NOT NULL ,
    		n int NOT NULL ,
    		CONSTRAINT PytanieSuwakowe_pk PRIMARY KEY  (id_pytania)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	/* 7 tabela */
	#$table_name = $wpdb->prefix.'Warunek';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE " .$table_name . " (
		id_pytania int NOT NULL ,
    		warunek int NOT NULL ,
    		CONSTRAINT Warunek_pk PRIMARY KEY (id_pytania,warunek)
	);";

	/* 8 tabela */
	#$table_name = $wpdb->prefix.'Termin';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE " .$table_name . " (
		id_pytania int NOT NULL ,
    		termin timestamp NOT NULL ,
		aktywne int NOT NULL ,
		wyniki int NOT NULL ,
    		CONSTRAINT Warunek_pk PRIMARY KEY (id_pytania,termin)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	$sql = "ALTER TABLE PytanieSuwakowe 
		ADD CONSTRAINT Dlugosc_suwaka_dla_pytania 
		FOREIGN KEY Dlugosc_suwaka_dla_pytania (id_pytania)
    		REFERENCES Pytanie (id_pytania)";
	dbDelta( $sql );

	$sql = "ALTER TABLE GlosyWyboru 
		ADD CONSTRAINT Glos 
		FOREIGN KEY Glos (id_odpowiedzi)
    		REFERENCES Odpowiedzi (id_odpowiedzi)";
	dbDelta( $sql );

	$sql = "ALTER TABLE GlosSuwakowy 
		ADD CONSTRAINT Glos_osoby 
		FOREIGN KEY Glos_osoby (id_osoby)
    		REFERENCES Osoba (id_osoby)";
	dbDelta( $sql );

	$sql = "ALTER TABLE GlosSuwakowy 
		ADD CONSTRAINT Glos_suwakowy 
		FOREIGN KEY Glos_suwakowy (id_pytania)
    		REFERENCES PytanieSuwakowe (id_pytania)";
	dbDelta( $sql );

	$sql = "ALTER TABLE GlosyWyboru 
		ADD CONSTRAINT Glosy_Osoba 
		FOREIGN KEY Glosy_Osoba (id_osoby)
    		REFERENCES Osoba (id_osoby)";
	dbDelta( $sql );

	$sql = "ALTER TABLE GlosyWyboru 
		ADD CONSTRAINT Glosy_Pytanie 
		FOREIGN KEY Glosy_Pytanie (id_pytania)
    		REFERENCES Pytanie (id_pytania)";
	dbDelta( $sql );

	$sql = "ALTER TABLE Odpowiedzi 
		ADD CONSTRAINT Odpowiedzi_na_pytanie 
		FOREIGN KEY Odpowiedzi_na_pytanie (id_pytania)
    		REFERENCES Pytanie (id_pytania)";
	dbDelta( $sql );

	$sql = "ALTER TABLE Pytanie 
		ADD CONSTRAINT Osoba_Pytanie 
		FOREIGN KEY Osoba_Pytanie (id_tworcy)
    		REFERENCES Osoba (id_osoby)";
	dbDelta( $sql );
	
	$sql = "ALTER TABLE Warunek 
		ADD CONSTRAINT Pytanie_Warunek 
		FOREIGN KEY Pytanie_Warunek (id_pytania)
    		REFERENCES Pytanie (id_pytania)";
	dbDelta( $sql );

	$sql = "ALTER TABLE Warunek 
		ADD CONSTRAINT Pytanie_Warunek 
		FOREIGN KEY Pytanie_Warunek (id_pytania)
    		REFERENCES Pytanie (id_pytania)";
	dbDelta( $sql );

	
}







?>

