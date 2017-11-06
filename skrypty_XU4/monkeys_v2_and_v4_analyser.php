<?php

  if (empty($argv[1])) die("Brak parametru nr_aplikacja\n");
    
  $nr_aplikacji = $argv[1];
  
  
  include( "monkeys_db_trafienia.php" );
  include( "monkeys_db_projekt.php" );
  include( "monkeys_katalogi.php" );   
  

  echo "START MONKEYS_".$nr_aplikacji."_ANALYZER\n";
  
  $katalog_zrodlowy = $katalog_sr;
  $katalog_docelowy = $katalog_srAnalyzed;
  $katalog_noUser = $katalog_srNoUser;
  
  $licznik = 0;
  $rozpoczeto = date("Y-m-d H:i:s");
  
   $db_trafienia = new mysqli($db_trafienia_serwer, $db_trafienia_user, $db_trafienia_haslo, "monkeys_".$nr_aplikacji."_trafienia", $db_trafienia_port);
  if ($db_trafienia->connect_error) {
    die("Błąd połaczenia db_trafienia: ".$db_trafienia->connect_error);
  }
  
  $db_projekt = new mysqli($db_projekt_serwer, $db_projekt_user, $db_projekt_haslo, $db_projekt_baza, $db_projekt_port);
  if ($db_projekt->connect_error)  {
    die("Błąd połaczenia db_projekt: ".$db_projekt->connect_error);
  }
  
  foreach (glob($katalog_zrodlowy."/*_".$nr_aplikacji."*") as $filename) {
  $plik = basename($filename);
   
   if ( filesize($katalog_zrodlowy."/".$plik) > 30 )
   {
       
 	  echo "Plik START 1: ".$plik."\n";
         //////ANALIZA MONKEYS_V2
//          1. wczytanie pliku do array
	 $wynik_v2 = file($katalog_zrodlowy."/".$plik);
	 
//          2. usunięcie pozycji 1 i ostaniej
	 unset($wynik_v2[ 0 ] );
	 unset($wynik_v2[ count( $wynik_v2 ) ] );
	 		
//          3. zapis do bazy pozostałych po explode spacji + nazwa usera
	if (count($wynik_v2) > 0) 	{	   
	   	   
	   $nazwa_usera = "";
	   //pobranie użytkownika który dał RESULT
	       
	   $sql = "SELECT `user`.`name` FROM `result` left join `user` on `result`.`userid` = `user`.`id` where `result`.`server_state` = 5 AND `result`.`client_state` = 5 AND `result`.`name` LIKE '%".$plik."%'";
// 	     echo "Zapytanie: ".$sql."\n";	   
	   $wynik_user = $db_projekt->query($sql);

	   if ($wynik_user->num_rows > 0) 
	   {
	     $user = $wynik_user->fetch_assoc();
	     $nazwa_usera = $user["name"];
	     $wynik_user->free();	    
// 	     echo "Nazwa usera: ".$nazwa_usera."\n";	
	    foreach ( $wynik_v2 as $pozycja )
	    {	     
// 	     echo "Znaleziony wynik: ".$pozycja."\n";	        
	      
	     $pozycja_array = explode( " ", $pozycja ); 		        
	     
	     $data_array = explode( "-", $pozycja_array[ 0 ] );
	     $data_nowa = $data_array[ 2 ]."-".$data_array[ 1 ]."-".$data_array[ 0 ];
	     unset($data_array);
	     
	     $sql = "INSERT INTO trafienia( data, czas, wylosowano, zgodnosc, nr_losowania, nazwa_usera, nazwa_wu ) VALUES ( "."\"".$data_nowa."\"".", "."\"".$pozycja_array[ 1 ]."\"".", "."\"".$pozycja_array[ 2 ]."\"".", "."\"".$pozycja_array[ 3 ]."\"".", "."\"".$pozycja_array[ 4 ]."\"".", "."\"".$nazwa_usera."\"".", "."\"".$plik."\""." );"; 	        	        
// 	     echo "Zapytanie: ".$sql."\n";
	     if ($db_trafienia->query( $sql ) === TRUE ) {
// 		echo "Dodano wynik do bazy"."\n";
	      } else {
	        echo "Zapytanie: ".$sql."\n";
		echo "Błąd dodawanie do bazy: ".$db_trafienia->error."\n";
	      }
	      unset($pozycja_array);	
	    }	     
	   } else {
	     echo "Brak usera dla: ".$plik."\n";
	     if (!copy($katalog_zrodlowy."/".$plik, $katalog_noUser."/".$plik)){       
	          }else{
				  unlink($katalog_zrodlowy."/".$plik);
			  }
	   }	   	   	   
	   	   
	   
	} else {
 	  //echo "Plik: ".$plik." nie ma wyników\n";
	}


//          4.zniszczenie array
	unset($wynik_v2);
	
         /////       
    }    
        if (!file_exists($katalog_docelowy."/".$plik)){
	  if (!copy($katalog_zrodlowy."/".$plik, $katalog_docelowy."/".$plik)){
			echo "Błąd kopiowania/przenoszenia pliku: ".$plik." do: ".$katalog_docelowy."/".$plik."\n";
         }else{
			//echo "skopiowano: ".$katalog_zrodlowy."/".$plik." <-> ".$katalog_docelowy."/".$plik."\n";
		    unlink($katalog_zrodlowy."/".$plik);
		 }   
        }else{
		    unlink($katalog_zrodlowy."/".$plik);
		 }
	$licznik++; 
  }	 
  
  
   echo "plików: ".$licznik."\n";
   
   //zapis danych do przerobione  
if ( $licznik > 0 ) { 
  $sql = "INSERT INTO przerobione ( rozpoczeto, zakonczono, przerobiono ) VALUES ( "."\"".$rozpoczeto."\"".", "."\"".date("Y-m-d H:i:s")."\"".", "."\"".$licznik."\""." );";	  
  echo "Zapytanie: ".$sql."\n"; 

  $db_trafienia->close();
  $db_trafienia = new mysqli($db_trafienia_serwer, $db_trafienia_user, $db_trafienia_haslo, "monkeys_".$nr_aplikacji."_trafienia", $db_trafienia_port);
  if ($db_trafienia->connect_error) {
    die("Błąd połaczenia db_trafienia: ".$db_trafienia->connect_error);
  }
  
  if ($db_trafienia->query( $sql ) === TRUE ) {
   		echo "Dodano wynik do bazy"."\n";
  } else {
     echo "Błąd dodawania przerobu do bazy: ".$db_trafienia->error."\n";
  }
  }
  
  $db_trafienia->close(); 
  $db_projekt->close();
  
  echo "KONIEC MONKEYS_".$nr_aplikacji."_ANALYZER\n";

?>
