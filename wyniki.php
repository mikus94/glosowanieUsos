<?php

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
				?>
					<option value="<?php echo $key; ?>"><?php echo $pyt[0]; ?></option>
				<?php 
			
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
				<img src="../wp-content/plugins/glosowanie/rate1.png">					
				<?php					
			}				
			if($gwiazdki[0] - $i > -1 ){
				?>
					<img src="../wp-content/plugins/glosowanie/rate.png">						
				<?php
				$i = $i + 1;
			}				
			for( $i = $i; $i <= 5; $i++){
				?>
				<img src="../wp-content/plugins/glosowanie/rate0.png">
				<?php
			}
			echo "<p><small> Średnia $gwiazdki[0] z $liczba_wszystkich[0] głosów.</small></p>";
			
		}
        	 
			
		
	}


?>
