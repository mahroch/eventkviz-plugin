<?php

//Setting for quizes - MODRA
// tento subor uz sa nepouzva a moze sa vymazat.
// ale chcem si ho nechat, lebo Samorin so Seeds este mozem potrebovat


class Eventkviz_Akcia_Class {
    public function __construct() {
        // Constructor code here
        if(!defined('BASE_URL')) {
            if($_SERVER['HTTP_HOST'] == 'localhost:8888') {
                define('BASE_URL', "http://localhost:8888/eventkviz/");
            } else {
                define('BASE_URL', "https://eventkviz.sk/");
            }
        }        

    }
}























class Eventkviz_samorin_Class extends Eventkviz_Akcia_Class {
  

    public function all_quizes_settings(){
       // global $all_quizes_settings;

        $this->all_quizes_settings['startup_form'] = true; 
            //moznost vstupit do kvizu na zaklade konkretnej URL (bez zadavania dodatocnych kodov), alebo pomocou formulara na zadanie kodu uzivatela a timu
            // true = zobraz formular na zadanie usera a timu 
            // false = udaje usera a timu sa nacitaju z URL
        $this->all_quizes_settings['identifikacia_kodom_usera'] = true;//moznost identifikovat uzivatela kodom
        $this->all_quizes_settings['verify_users_in_db'] = false;//moznost verifikovat uzovatela v predvyplnenej databaze
        $this->all_quizes_settings['identifikacia_userov_timu'] = true;//moznost identifikovania viacerych uzivatelov v ramci jedneho timu . FALSE-ak je zoznam userov s priradenymi timami, tim sa vyberie automaticky.
        $this->all_quizes_settings['select_from_teams_array'] = true; // ma sa team vybrat z vopred zadaneho zoznamu, zoznam nizsie.
        $this->all_quizes_settings['select_teams'] = array(
            '' => 'Select ...',
            'modra' => 'Modrá',
            'zlta' => 'Žltá',
            'cervena' => 'Červená',
            'zelena' => 'Zelená',
            'bordova' => 'Bordová',
            'oranzova' => 'Oranžová',
            'ruzova' => 'Ružová',
            'zlata' => 'Zlatá'
            );

    
        $this->all_quizes_settings['use_seed'] = false; // pri ziskani stanovista sa userovi ukaze kod za dane stanoviste
        $this->all_quizes_settings['minimal_number_of_correct_seeds'] = 3; // minimlalny pocet spravnych seedov na otvorenie truhlice
        $this->all_quizes_settings['final_place_pocet_pokusov'] = 3; // max pocet pokusov na otvorenie truhlice
        $this->all_quizes_settings['places'] = array ( // pouziva sa len ak sa pouzivaju seeds
            	0 => array ('sudoku', 'Manga Boy'),
                1 => array ('movies', 'Charlie Chaplin'),
                2 => array ('music', 'Michael Jackson'),
                3 => array ('knowledge', 'Albert Einstein')
        );
        
        $this->all_quizes_settings['names_of_places'] = array ( // pouziva sa len ak sa pouzivaju seeds
           'sudoku' => 'Manga Boy',
            'movies' =>  'Charlie Chaplin',
            'music' =>  'Michael Jackson',
            'knowledge' =>  'Albert Einstein'
        );

         if($_SERVER['HTTP_HOST'] == 'localhost:8888') {
                $this->all_quizes_settings['no_quiz_places_urls'] = array ( // url na unique page, ktora zobrazuje dany seed. je to preto takto, aby sa nedal z URL odcitat kod, a ucastnik hacker by nemusel ist na miesto a kody by nasiel sam
                    'horse' => 'http://localhost:8888/eventkviz/samorin/klsdpd/',
                    'racing' => 'http://localhost:8888/eventkviz/samorin/jazkrperv/',
                    'stadium' => 'http://localhost:8888/eventkviz/samorin/fbql/',
                    'bridge' => 'http://localhost:8888/eventkviz/samorin/breytetre/',
                    'hotel' => 'http://localhost:8888/eventkviz/samorin/herpwrew/',
                    'danube' => 'http://localhost:8888/eventkviz/samorin/dprkbnd/',
                    'final' => 'http://localhost:8888/eventkviz/samorin/pidsfsdffss/',
                );
            } else {
                $url = 'https://eventkviz.sk/sudoku-quiz-evaluation-dynamic/';
                $this->all_quizes_settings['no_quiz_places_urls'] = array ( // url na unique page, ktora zobrazuje dany seed. je to preto takto, aby sa nedal z URL odcitat kod, a ucastnik hacker by nemusel ist na miesto a kody by nasiel sam
                    'horse' => 'https://eventkviz.sk/samorin/klsdpd/',
                    'racing' => 'https://eventkviz.sk/samorin/jazkrperv/',
                    'stadium' => 'https://eventkviz.sk/samorin/fbql/',
                    'bridge' => 'https://eventkviz.sk/samorin/breytetre/',
                    'hotel' => 'https://eventkviz.sk/samorin/herpwrew/',
                    'danube' => 'https://eventkviz.sk/samorin/dprkbnd/',
                    'final' => 'https://eventkviz.sk/samorin/pidsfsdffss/',
                );

            }



        $this->all_quizes_settings['credits'] = array ( // kredity za najdenie miesta bez kvizu a vygenerovanie seedu. POcet a tag miest musi sediet s poctom nekvizovych
                'horse'  => 10,
                'racing' => 20,
                'stadium' => 40,
                'bridge' => 50,
                'hotel' => 30,
                'danube' => 60,
                'final' => 20, // najdenie finalneho miesta
                'chest_success' => 100, // otvorenie truhlice
                'unspecified' => 30
        );

        $this->all_quizes_settings['poslat_vysledok_usera_mailom'] = false; //moznost poslat aktualny vysledok uzivatela po dosiahnuti MIESTA (nekvizoveho)  na zadany email
        $this->all_quizes_settings['admin_mail'] = 'mahroch@gmail.com'; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
$this->all_quizes_settings['show_link_back_to_all_quizes'] = false;  // tru/false zobraz linku na preklik spat na vsetky kvizy    

            
    }


    public function music_quiz_settings(){
       // global $music_settings;
        
        $this->music_settings['music_quiz_active'] = true;
        $this->music_settings['credits'] = array (
            'corr_art_corr_pos_corr_song_corr_pos' => 100,//spravne meno spevaka aj skladby, obe na spravnom mieste
            'corr_art_corr_pos_incorr_song' => 50,// spravny autor, ale nespravna skladba na danom mieste
            'incorr_art_corr_song_corr_pos' => 40,// nespravny autor, ale spravna skladba na danom mieste
            'corr_art_in_array' => 30,//spravne uvedeny autor v ramci celeho setu otazok (tj. v ramci celeho setu urcil spravneho autora, ale na nespravnom mieste)
            'corr_song_in_array' => 20  // spravne uvedena skladba v ramci celeho setu otazok (tj. v ramci celeho setu urcil spravnu skladbu, ale na nespravnom mieste)
        );
        
        //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->music_settings['pocet_otazok_v_sete'] = 10; // 0/cislo .... 0 znamena, ze sa vybera podla zadneho mnozstva v production settingu , 
        $this->music_settings['production'] = 'all'; //skcz/zahranicne/all 
        
        $this->music_settings['poslat_vysledok_usera_mailom'] = false; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        $this->music_settings['admin_mail'] = $this->all_quizes_settings['admin_mail']; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        
        $this->music_settings['zobraz_spravne_odpovede'] = true;  //moznost vybrat ci sa uzivatelovi zobrazia spravne odpovede po odoslani kvizu, alebo nezobrazia
        $this->music_settings['pocet_pokusov'] = 1;  //moznost rozhodnut sa ci moze uzivatel absolvovat kviz len raz, alebo viac krat (da sa urcit pocet kolko krat) 

    }

    public function movies_quiz_settings(){
        //global $movies_settings;
        
        $this->movies_settings['movies_quiz_active'] = true;
         $this->movies_settings['movies_quiz_type'] = "full"; // full - na vyber zo vsetkych filmov po zadani pisemenok, choices - na vyber z 10 predvybratyvh filmov

        $this->movies_settings['credits'] = array (
            'corr_movie' => 30,
            'corr_movie_wrong_pos' => 0,
        );
        
        //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->movies_settings['pocet_otazok_v_sete'] = 10; // 0/cislo .... 0 znamena, ze sa vybera podla zadneho mnozstva v production settingu , 
        $this->movies_settings['production'] = 'all'; // skcz/zahranicne/all 
        
        $movies_settings['number_question_in_production'] = array ( 
            'skcz' => 2,
            'zahranicne' => 8
            );

        $this->movies_settings['poslat_vysledok_usera_mailom'] = false; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        $this->movies_settings['admin_mail'] = $this->all_quizes_settings['admin_mail']; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        
        $this->movies_settings['zobraz_spravne_odpovede'] = true;  //moznost vybrat ci sa uzivatelovi zobrazia spravne odpovede po odoslani kvizu, alebo nezobrazia
        $this->movies_settings['pocet_pokusov'] = 1;  //moznost rozhodnut sa ci moze uzivatel absolvovat kviz len raz, alebo viac krat (da sa urcit pocet kolko krat) 

    }

        
    public function knowledge_quiz_settings(){
        //global $knowledge_settings;
        
        $this->knowledge_settings['knowledge_quiz_active'] = true;
        $this->knowledge_settings['credits'] = array (
            'corr_answer' => 30
        );
        
        //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->knowledge_settings['pocet_otazok_v_sete'] = 0; // 0/cislo .... 0 znamena, ze sa vybera podla zadneho mnozstva v topic settingu , 
        
        $this->knowledge_settings['topic'] = 'all'; // jeden topic/all 
        $this->knowledge_settings['number_question_in_topic'] = array ( 
            'visual' => 2,
            'mathematical' => 2,
            'geography' => 2,
            'general' => 4,
            'movies' => 5,
            'colonnade' => 0
        ); 
        
        $this->knowledge_settings['poslat_vysledok_usera_mailom'] = false; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        $this->knowledge_settings['admin_mail'] = $this->all_quizes_settings['admin_mail']; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        
        $this->knowledge_settings['zobraz_spravne_odpovede'] = true;  //moznost vybrat ci sa uzivatelovi zobrazia spravne odpovede po odoslani kvizu, alebo nezobrazia
        $this->knowledge_settings['pocet_pokusov'] = 1;  //moznost rozhodnut sa ci moze uzivatel absolvovat kviz len raz, alebo viac krat (da sa urcit pocet kolko krat) 

    }

    public function sudoku_quiz_settings(){
        //global $sudoku_settings;
        
        $this->sudoku_settings['sudoku_quiz_active'] = true;
        $this->sudoku_settings['credits'] = array (
            'easy' => 10,
            'medium' => 20,
            'hard' => 35,
        );
            
        $this->sudoku_settings['pocet_otazok_v_sete'] = 1; //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->sudoku_settings['moze_si_vybrat_difficulty'] = 'no'; // yes/no 
        
        $this->sudoku_settings['default_difficulty'] = 'hard'; // hard/medium/easy 
        
        $this->sudoku_settings['poslat_vysledok_usera_mailom'] = false; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        $this->sudoku_settings['admin_mail'] = $this->all_quizes_settings['admin_mail']; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        
        $this->sudoku_settings['zobraz_spravne_odpovede'] = true;  //moznost vybrat ci sa uzivatelovi zobrazia spravne odpovede po odoslani kvizu, alebo nezobrazia
        $this->sudoku_settings['pocet_pokusov'] = 1;  //moznost rozhodnut sa ci moze uzivatel absolvovat kviz len raz, alebo viac krat (da sa urcit pocet kolko krat)  

    }
}



class Eventkviz_esmt_Class extends Eventkviz_Akcia_Class {
    public function __construct() {
        // Constructor code here
    }

    public function all_quizes_settings(){
       // global $all_quizes_settings;

        $this->all_quizes_settings['startup_form'] = true; 
            //moznost vstupit do kvizu na zaklade konkretnej URL (bez zadavania dodatocnych kodov), alebo pomocou formulara na zadanie kodu uzivatela a timu
            // true = zobraz formular na zadanie usera a timu 
            // false = udaje usera a timu sa nacitaju z URL
        $this->all_quizes_settings['identifikacia_kodom_usera'] = false;//moznost identifikovat uzivatela kodom
        $this->all_quizes_settings['verify_users_in_db'] = false;//moznost verifikovat uzovatela v predvyplnenej databaze
        $this->all_quizes_settings['identifikacia_userov_timu'] = true;//moznost identifikovania viacerych uzivatelov v ramci jedneho timu . FALSE-ak je zoznam userov s priradenymi timami, tim sa vyberie automaticky.
        $this->all_quizes_settings['select_from_teams_array'] = true; // ma sa team vybrat z vopred zadaneho zoznamu, zoznam nizsie.
        $this->all_quizes_settings['select_teams'] = array(
            '' => 'Select ...',
            'team1' => 'Team 1',
            'team2' => 'Team 2',
            'team3' => 'Team 3',
            'team4' => 'Team 4',
            'team5' => 'Team 5',
            'team6' => 'Team 6',
            'team7' => 'Team 7',
            'team8' => 'Team 8'
            );
        $this->all_quizes_settings['use_seed'] = false; // pri ziskani stanovista sa userovi ukaze kod za dane stanoviste
        $this->all_quizes_settings['places'] = array ( // pouziva sa len ak sa pouzivaju seeds
            0 =>  array ('sudoku', 'Sudoku quiz'),
            1 =>  array ('movies', 'Movies quiz'),
            2 =>  array ('music', 'Music quiz'),
            3 => array ('knowledge', 'Knowledge quiz'),
            
        );
        $this->all_quizes_settings['names_of_places'] = array ( // pouziva sa len ak sa pouzivaju seeds
            'sudoku' => 'Sudoku quiz',
            'movies' => 'Movies quiz',
            'music' => 'Music quiz',
            'knowledge' => 'Knowledge quiz',
            
        );
       $this->all_quizes_settings['show_link_back_to_all_quizes'] = false;  // tru/false zobraz linku na preklik spat na vsetky kvizy        
            
    }
    

    public function music_quiz_settings(){
       // global $music_settings;
        
        $this->music_settings['music_quiz_active'] = true;
        $this->music_settings['show_entry_form'] = false; // true/false, pouziva sa ked potrebujem jednu URL na tento kviz ukazat viacerym timom, ktori si pred kvizom musia vybrat svoj tim. al enie cez all links, ale len pre tento konkretny.
        //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->music_settings['credits'] = array (
            'corr_art_corr_pos_corr_song_corr_pos' => 100,//spravne meno spevaka aj skladby, obe na spravnom mieste
            'corr_art_corr_pos_incorr_song' => 50,// spravny autor, ale nespravna skladba na danom mieste
            'incorr_art_corr_song_corr_pos' => 40,// nespravny autor, ale spravna skladba na danom mieste
            'corr_art_in_array' => 0,//spravne uvedeny autor v ramci celeho setu otazok (tj. v ramci celeho setu urcil spravneho autora, ale na nespravnom mieste)
            'corr_song_in_array' => 0  // spravne uvedena skladba v ramci celeho setu otazok (tj. v ramci celeho setu urcil spravnu skladbu, ale na nespravnom mieste)
        );
        
        //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->music_settings['pocet_otazok_v_sete'] = 10; // 0/cislo .... 0 znamena, ze sa vybera podla zadneho mnozstva v production settingu , 
        $this->music_settings['production'] = 'zahranicne'; //skcz/zahranicne/all 
        
        $this->music_settings['poslat_vysledok_usera_mailom'] = true; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        $this->music_settings['admin_mail'] = 'mahroch@gmail.com'; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        
        $this->music_settings['zobraz_spravne_odpovede'] = true;  //moznost vybrat ci sa uzivatelovi zobrazia spravne odpovede po odoslani kvizu, alebo nezobrazia
        $this->music_settings['pocet_pokusov'] = 1;  //moznost rozhodnut sa ci moze uzivatel absolvovat kviz len raz, alebo viac krat (da sa urcit pocet kolko krat) 

    }

    public function movies_quiz_settings(){
        //global $movies_settings;
        
        $this->movies_settings['movies_quiz_active'] = true;
        $this->movies_settings['show_entry_form'] = false; // true/false, pouziva sa ked potrebujem jednu URL na tento kviz ukazat viacerym timom, ktori si pred kvizom musia vybrat svoj tim. al enie cez all links, ale len pre tento konkretny.
        //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->movies_settings['movies_quiz_type'] = "full"; // full - na vyber zo vsetkych filmov po zadani pisemenok, choices - na vyber z 10 predvybratyvh filmov

        $this->movies_settings['credits'] = array (
            'corr_movie' => 30,
            'corr_movie_wrong_pos' => 0,
        );
        
        //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->movies_settings['pocet_otazok_v_sete'] = 10; // 0/cislo .... 0 znamena, ze sa vybera podla zadneho mnozstva v production settingu , 
        $this->movies_settings['production'] = 'zahranicne'; // skcz/zahranicne/all 
        
        $this->movies_settings['poslat_vysledok_usera_mailom'] = true; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        $this->movies_settings['admin_mail'] = 'mahroch@gmail.com'; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        
        $this->movies_settings['zobraz_spravne_odpovede'] = true;  //moznost vybrat ci sa uzivatelovi zobrazia spravne odpovede po odoslani kvizu, alebo nezobrazia
        $this->movies_settings['pocet_pokusov'] = 1;  //moznost rozhodnut sa ci moze uzivatel absolvovat kviz len raz, alebo viac krat (da sa urcit pocet kolko krat) 

    }

        
    public function knowledge_quiz_settings(){
        //global $knowledge_settings;
        
        $this->knowledge_settings['knowledge_quiz_active'] = true;
        $this->knowledge_settings['show_entry_form'] = true; // true/false, pouziva sa ked potrebujem jednu URL na tento kviz ukazat viacerym timom, ktori si pred kvizom musia vybrat svoj tim. al enie cez all links, ale len pre tento konkretny.
        //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->knowledge_settings['credits'] = array (
            'corr_answer' => 30
        );
        $this->knowledge_settings['show_entry_form'] = true; // true/false, pouziva sa ked potrebujem jednu URL na tento kviz ukazat viacerym timom, ktori si pred kvizom musia vybrat svoj tim. al enie cez all links, ale len pre tento konkretny.
        //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->knowledge_settings['pocet_otazok_v_sete'] = 10; // 0/cislo .... 0 znamena, ze sa vybera podla zadneho mnozstva v topic settingu , 
        
        $this->knowledge_settings['topic'] = 'all'; // jeden topic/all 
        $this->knowledge_settings['number_question_in_topic'] = array ( 
            'visual' => 5,
            'mathematical' => 5,
            'geography' => 5,
            'general' => 5,
            'movies' => 5,
            'colonnade' => 0
        ); 
        
        $this->knowledge_settings['poslat_vysledok_usera_mailom'] = true; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        $this->knowledge_settings['admin_mail'] = 'mahroch@gmail.com'; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        
        $this->knowledge_settings['zobraz_spravne_odpovede'] = true;  //moznost vybrat ci sa uzivatelovi zobrazia spravne odpovede po odoslani kvizu, alebo nezobrazia
        $this->knowledge_settings['pocet_pokusov'] = 1;  //moznost rozhodnut sa ci moze uzivatel absolvovat kviz len raz, alebo viac krat (da sa urcit pocet kolko krat) 

    }

    public function sudoku_quiz_settings(){
        //global $sudoku_settings;
        
        $this->sudoku_settings['sudoku_quiz_active'] = false;
        $this->sudoku_settings['show_entry_form'] = false; // true/false, pouziva sa ked potrebujem jednu URL na tento kviz ukazat viacerym timom, ktori si pred kvizom musia vybrat svoj tim. al enie cez all links, ale len pre tento konkretny.
        //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->sudoku_settings['credits'] = array (
            'easy' => 10,
            'medium' => 20,
            'hard' => 35,
        );
            
        $this->sudoku_settings['pocet_otazok_v_sete'] = 1; //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->sudoku_settings['moze_si_vybrat_difficulty'] = 'yes'; // yes/no 
        
        $this->sudoku_settings['default_difficulty'] = 'hard'; // hard/medium/easy 
        $this->sudoku_settings['poslat_vysledok_usera_mailom'] = false; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        $this->sudoku_settings['admin_mail'] = 'mahroch@gmail.com'; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        
        $this->sudoku_settings['zobraz_spravne_odpovede'] = true;  //moznost vybrat ci sa uzivatelovi zobrazia spravne odpovede po odoslani kvizu, alebo nezobrazia
        $this->sudoku_settings['pocet_pokusov'] = 1;  //moznost rozhodnut sa ci moze uzivatel absolvovat kviz len raz, alebo viac krat (da sa urcit pocet kolko krat)  

    }
}


class Eventkviz_event_Class extends Eventkviz_Akcia_Class {
    public function __construct() {
        // Constructor code here
    }

    public function all_quizes_settings(){
       // global $all_quizes_settings;

        $this->all_quizes_settings['startup_form'] = true; 
            //moznost vstupit do kvizu na zaklade konkretnej URL (bez zadavania dodatocnych kodov), alebo pomocou formulara na zadanie kodu uzivatela a timu
            // true = zobraz formular na zadanie usera a timu 
            // false = udaje usera a timu sa nacitaju z URL
        $this->all_quizes_settings['identifikacia_kodom_usera'] = false;// true/false - moznost identifikovat uzivatela kodom
        $this->all_quizes_settings['verify_users_in_db'] = false;// true/false - moznost verifikovat uzovatela v predvyplnenej databaze
        $this->all_quizes_settings['identifikacia_userov_timu'] = true;// true/false - moznost identifikovania viacerych uzivatelov v ramci jedneho timu . FALSE-ak je zoznam userov s priradenymi timami, tim sa vyberie automaticky.
        $this->all_quizes_settings['select_from_teams_array'] = true; // true/false - ma sa team vybrat z vopred zadaneho zoznamu, zoznam nizsie.
        $this->all_quizes_settings['select_teams'] = array(
            '' => 'Select ...',
            'team1' => 'Team 1',
            'team2' => 'Team 2',
            'team3' => 'Team 3',
            'team4' => 'Team 4',
            'team5' => 'Team 5',
            'team6' => 'Team 6',
            'team7' => 'Team 7',
            'team8' => 'Team 8',
            'team9' => 'Team 9',
            'team10' => 'Team 10'
            );
        $this->all_quizes_settings['use_seed'] = false; // true/false - pri ziskani stanovista sa userovi ukaze kod za dane stanoviste
        $this->all_quizes_settings['places'] = array ( // pouziva sa len ak sa pouzivaju seeds
            0 =>  array ('sudoku', 'Sudoku quiz'),
            1 =>  array ('movies', 'Movies quiz'),
            2 =>  array ('music', 'Music quiz'),
            3 => array ('knowledge', 'Knowledge quiz'),
            
        );
        $this->all_quizes_settings['names_of_places'] = array ( // pouziva sa len ak sa pouzivaju seeds
            'sudoku' => 'Sudoku quiz',
            'movies' => 'Movies quiz',
            'music' => 'Music quiz',
            'knowledge' => 'Knowledge quiz',
            
        );
        $this->all_quizes_settings['show_link_back_to_all_quizes'] = false;  // tru/false zobraz linku na preklik spat na vsetky kvizy    
            
    }
    

    public function music_quiz_settings(){
       // global $music_settings;
        
        $this->music_settings['music_quiz_active'] = true;
           $this->music_settings['show_entry_form'] = true; // true/false, pouziva sa ked potrebujem jednu URL na tento kviz ukazat viacerym timom, ktori si pred kvizom musia vybrat svoj tim. al enie cez all links, ale len pre tento konkretny.
        //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->music_settings['credits'] = array (
            'corr_art_corr_pos_corr_song_corr_pos' => 100,//spravne meno spevaka aj skladby, obe na spravnom mieste
            'corr_art_corr_pos_incorr_song' => 50,// spravny autor, ale nespravna skladba na danom mieste
            'incorr_art_corr_song_corr_pos' => 50,// nespravny autor, ale spravna skladba na danom mieste
            'corr_art_in_array' => 0,//spravne uvedeny autor v ramci celeho setu otazok (tj. v ramci celeho setu urcil spravneho autora, ale na nespravnom mieste)
            'corr_song_in_array' => 0  // spravne uvedena skladba v ramci celeho setu otazok (tj. v ramci celeho setu urcil spravnu skladbu, ale na nespravnom mieste)
        );
        
        //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->music_settings['pocet_otazok_v_sete'] = 10; // 0/cislo .... 0 znamena, ze sa vybera podla zadneho mnozstva v production settingu , 
        $this->music_settings['production'] = 'all'; //skcz/zahranicne/all 
        
        $this->music_settings['poslat_vysledok_usera_mailom'] = false; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        $this->music_settings['admin_mail'] = 'mahroch@gmail.com'; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        
        $this->music_settings['zobraz_spravne_odpovede'] = false;  //moznost vybrat ci sa uzivatelovi zobrazia spravne odpovede po odoslani kvizu, alebo nezobrazia
        $this->music_settings['zobraz_spravne_uhadnute_odpovede'] = true;  //moznost vybrat ci sa uzivatelovi zobrazia JEHO VLASTNE spravne odpovede po odoslani kvizu, alebo nezobrazia
        $this->music_settings['pocet_pokusov'] = 10;  //moznost rozhodnut sa ci moze uzivatel absolvovat kviz len raz, alebo viac krat (da sa urcit pocet kolko krat) 

        $this->music_settings['min_body_na_postup'] = 400;  //pocet bodov na splnenie kvizu - pouziva sa na zobrazenie riadku sudoku, alebo ineho kluca. Ak je 0, tak sa nekontroluje pocet.
        $this->music_settings['obrazok_pri_splneni_kvizu'] = 1853;  //ID obrazku z media library, ktory sa zobrazi po splneni kvizu
        

    }

    public function movies_quiz_settings(){
        //global $movies_settings;
        
        $this->movies_settings['movies_quiz_active'] = true;
        $this->movies_settings['show_entry_form'] = true; // true/false, pouziva sa ked potrebujem jednu URL na tento kviz ukazat viacerym timom, ktori si pred kvizom musia vybrat svoj tim. al enie cez all links, ale len pre tento konkretny.
        //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->movies_settings['movies_quiz_type'] = "full"; // full/choices - full - na vyber zo vsetkych filmov po zadani pisemenok, choices - na vyber z 10 predvybratyvh filmov

        $this->movies_settings['credits'] = array (
            'corr_movie' => 100,
            'corr_movie_wrong_pos' => 0,
        );
        
        //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->movies_settings['pocet_otazok_v_sete'] = 10; // 0/cislo .... 0 znamena, ze sa vybera podla zadneho mnozstva v production settingu , 
        $this->movies_settings['production'] = 'all'; // skcz/zahranicne/all 
        
        $this->movies_settings['poslat_vysledok_usera_mailom'] = false; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
         $this->movies_settings['admin_mail'] = 'mahroch@gmail.com'; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        
        $this->movies_settings['zobraz_spravne_odpovede'] = false;  //moznost vybrat ci sa uzivatelovi zobrazia spravne odpovede po odoslani kvizu, alebo nezobrazia
        $this->movies_settings['zobraz_spravne_uhadnute_odpovede'] = true;  //moznost vybrat ci sa uzivatelovi zobrazia JEHO VLASTNE spravne odpovede po odoslani kvizu, alebo nezobrazia
        $this->movies_settings['pocet_pokusov'] = 10;  //moznost rozhodnut sa ci moze uzivatel absolvovat kviz len raz, alebo viac krat (da sa urcit pocet kolko krat) 

        $this->movies_settings['min_body_na_postup'] = 400;  //pocet bodov na splnenie kvizu - pouziva sa na zobrazenie riadku sudoku, alebo ineho kluca. Ak je 0, tak sa nekontroluje pocet.
        $this->movies_settings['obrazok_pri_splneni_kvizu'] = 1852;  //ID obrazku z media library, ktory sa zobrazi po splneni kvizu
        

    }

        
    public function knowledge_quiz_settings(){
        //global $knowledge_settings;
        
        $this->knowledge_settings['knowledge_quiz_active'] = true;
        $this->knowledge_settings['show_entry_form'] = true; // true/false, pouziva sa ked potrebujem jednu URL na tento kviz ukazat viacerym timom, ktori si pred kvizom musia vybrat svoj tim. al enie cez all links, ale len pre tento konkretny.
        //moznost zvolit si kolko otazok v sete dostane uzivatel

        $this->knowledge_settings['credits'] = array (
            'corr_answer' => 100
        );
        
        //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->knowledge_settings['pocet_otazok_v_sete'] = 0; // 0/cislo .... 0 znamena, ze sa vybera podla zadneho mnozstva v topic settingu , 
        
        $this->knowledge_settings['topic'] = 'all'; // jeden topic/all 
        $this->knowledge_settings['number_question_in_topic'] = array ( 
            'visual' => 0,
            'mathematical' =>0,
            'geography' => 0,
            'general' => 0,
            'movies' => 0,
            'viglas' => 15
        ); 
        
        $this->knowledge_settings['poslat_vysledok_usera_mailom'] = false; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email

        $this->knowledge_settings['admin_mail'] = 'mahroch@gmail.com'; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
  
        $this->knowledge_settings['zobraz_spravne_odpovede'] = false;  //moznost vybrat ci sa uzivatelovi zobrazia spravne odpovede po odoslani kvizu, alebo nezobrazia
        $this->knowledge_settings['zobraz_spravne_uhadnute_odpovede'] = true;  //moznost vybrat ci sa uzivatelovi zobrazia JEHO VLASTNE spravne odpovede po odoslani kvizu, alebo nezobrazia

        $this->knowledge_settings['pocet_pokusov'] = 10;  //moznost rozhodnut sa ci moze uzivatel absolvovat kviz len raz, alebo viac krat (da sa urcit pocet kolko krat) 

        $this->knowledge_settings['min_body_na_postup'] = 400;  //pocet bodov na splnenie kvizu - pouziva sa na zobrazenie riadku sudoku, alebo ineho kluca. Ak je 0, tak sa nekontroluje pocet.
        $this->knowledge_settings['obrazok_pri_splneni_kvizu'] = 1851;  //ID obrazku z media library, ktory sa zobrazi po splneni kvizu
        

    }

    public function sudoku_quiz_settings(){
        //global $sudoku_settings;
        
        $this->sudoku_settings['sudoku_quiz_active'] = false;
           $this->sudoku_settings['show_entry_form'] = false; // true/false, pouziva sa ked potrebujem jednu URL na tento kviz ukazat viacerym timom, ktori si pred kvizom musia vybrat svoj tim. al enie cez all links, ale len pre tento konkretny.
        //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->sudoku_settings['credits'] = array (
            'easy' => 10,
            'medium' => 20,
            'hard' => 35,
        );
            
        $this->sudoku_settings['pocet_otazok_v_sete'] = 1; //moznost zvolit si kolko otazok v sete dostane uzivatel
        $this->sudoku_settings['moze_si_vybrat_difficulty'] = 'yes'; // yes/no 
        
        $this->sudoku_settings['default_difficulty'] = 'hard'; // hard/medium/easy 
        $this->sudoku_settings['poslat_vysledok_usera_mailom'] = false; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        $this->sudoku_settings['admin_mail'] = 'mahroch@gmail.com'; //moznost poslat aktualny vysledok uzivatela po odoslani  na zadany email
        
        $this->sudoku_settings['zobraz_spravne_odpovede'] = true;  //moznost vybrat ci sa uzivatelovi zobrazia spravne odpovede po odoslani kvizu, alebo nezobrazia
        $this->sudoku_settings['zobraz_spravne_uhadnute_odpovede'] = true;  //moznost vybrat ci sa uzivatelovi zobrazia JEHO VLASTNE spravne odpovede po odoslani kvizu, alebo nezobrazia
        $this->sudoku_settings['pocet_pokusov'] = 1;  //moznost rozhodnut sa ci moze uzivatel absolvovat kviz len raz, alebo viac krat (da sa urcit pocet kolko krat)  

    }
}