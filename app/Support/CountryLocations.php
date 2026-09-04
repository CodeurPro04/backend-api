<?php

namespace App\Support;

use App\Models\Country;

class CountryLocations
{
    private const REAL_LOCATIONS = [
        'CI' => [
            ['city' => 'Abidjan', 'commune' => 'Cocody', 'quartier' => 'Deux Plateaux', 'address' => 'Sofitel Abidjan Hôtel Ivoire, Boulevard Hassan II, Cocody, Abidjan', 'postal_code' => 'CI-08 BP'],
            ['city' => 'Abidjan', 'commune' => 'Plateau', 'quartier' => 'Plateau', 'address' => 'Avenue Noguès, Plateau, Abidjan', 'postal_code' => 'CI-01 BP'],
            ['city' => 'Abidjan', 'commune' => 'Marcory', 'quartier' => 'Zone 4', 'address' => 'Rue Paul Langevin, Zone 4, Marcory, Abidjan', 'postal_code' => 'CI-11 BP'],
            ['city' => 'Yamoussoukro', 'commune' => 'Centre-ville', 'quartier' => 'Fondation', 'address' => 'Fondation Félix Houphouët-Boigny, Yamoussoukro', 'postal_code' => 'CI-09 BP'],
        ],
        'SN' => [
            ['city' => 'Dakar', 'commune' => 'Plateau', 'quartier' => 'Indépendance', 'address' => 'Place de l’Indépendance, Dakar Plateau, Dakar', 'postal_code' => 'SN-10000'],
            ['city' => 'Dakar', 'commune' => 'Almadies', 'quartier' => 'Ngor', 'address' => 'Route des Almadies, Ngor, Dakar', 'postal_code' => 'SN-12000'],
            ['city' => 'Dakar', 'commune' => 'Mermoz', 'quartier' => 'Sacré-Cœur', 'address' => 'VDN, Mermoz Sacré-Cœur, Dakar', 'postal_code' => 'SN-11000'],
            ['city' => 'Saly', 'commune' => 'Saly Portudal', 'quartier' => 'Station balnéaire', 'address' => 'Saly Portudal, Mbour, Sénégal', 'postal_code' => 'SN-23002'],
        ],
        'GH' => [
            ['city' => 'Accra', 'commune' => 'Osu', 'quartier' => 'Oxford Street', 'address' => 'Oxford Street, Osu, Accra', 'postal_code' => 'GH-GA-184'],
            ['city' => 'Accra', 'commune' => 'Airport Residential', 'quartier' => 'Airport', 'address' => 'Airport Residential Area, Accra', 'postal_code' => 'GH-GA-015'],
            ['city' => 'Accra', 'commune' => 'Cantonments', 'quartier' => 'Embassy Area', 'address' => 'Cantonments Road, Accra', 'postal_code' => 'GH-GA-063'],
            ['city' => 'Kumasi', 'commune' => 'Nhyiaeso', 'quartier' => 'Nhyiaeso', 'address' => 'Nhyiaeso, Kumasi', 'postal_code' => 'GH-AK-039'],
        ],
        'NG' => [
            ['city' => 'Lagos', 'commune' => 'Victoria Island', 'quartier' => 'Eko Atlantic', 'address' => 'Ahmadu Bello Way, Victoria Island, Lagos', 'postal_code' => 'NG-101241'],
            ['city' => 'Lagos', 'commune' => 'Ikoyi', 'quartier' => 'Bourdillon', 'address' => 'Bourdillon Road, Ikoyi, Lagos', 'postal_code' => 'NG-101233'],
            ['city' => 'Lagos', 'commune' => 'Lekki', 'quartier' => 'Phase 1', 'address' => 'Admiralty Way, Lekki Phase 1, Lagos', 'postal_code' => 'NG-106104'],
            ['city' => 'Abuja', 'commune' => 'Maitama', 'quartier' => 'Maitama', 'address' => 'Maitama, Abuja, Federal Capital Territory', 'postal_code' => 'NG-900271'],
        ],
        'CM' => [
            ['city' => 'Douala', 'commune' => 'Bonapriso', 'quartier' => 'Bonapriso', 'address' => 'Rue Njo-Njo, Bonapriso, Douala', 'postal_code' => 'CM-00237'],
            ['city' => 'Douala', 'commune' => 'Akwa', 'quartier' => 'Akwa', 'address' => 'Boulevard de la Liberté, Akwa, Douala', 'postal_code' => 'CM-00237'],
            ['city' => 'Yaoundé', 'commune' => 'Bastos', 'quartier' => 'Bastos', 'address' => 'Quartier Bastos, Yaoundé', 'postal_code' => 'CM-00237'],
            ['city' => 'Kribi', 'commune' => 'Centre-ville', 'quartier' => 'Front de mer', 'address' => 'Plage de Kribi, Kribi, Cameroun', 'postal_code' => 'CM-00237'],
        ],
        'BF' => [
            ['city' => 'Ouagadougou', 'commune' => 'Ouaga 2000', 'quartier' => 'Ouaga 2000', 'address' => 'Avenue Pascal Zagré, Ouaga 2000, Ouagadougou', 'postal_code' => 'BF-01 BP'],
            ['city' => 'Ouagadougou', 'commune' => 'Koulouba', 'quartier' => 'Centre-ville', 'address' => 'Avenue Kwame Nkrumah, Ouagadougou', 'postal_code' => 'BF-03 BP'],
            ['city' => 'Bobo-Dioulasso', 'commune' => 'Centre-ville', 'quartier' => 'Dioulassoba', 'address' => 'Place de la Nation, Bobo-Dioulasso', 'postal_code' => 'BF-01 BP'],
        ],
        'BJ' => [
            ['city' => 'Cotonou', 'commune' => 'Haie Vive', 'quartier' => 'Haie Vive', 'address' => 'Boulevard de la Marina, Cotonou', 'postal_code' => 'BJ-01 BP'],
            ['city' => 'Cotonou', 'commune' => 'Fidjrossè', 'quartier' => 'Plage', 'address' => 'Route des Pêches, Fidjrossè, Cotonou', 'postal_code' => 'BJ-01 BP'],
            ['city' => 'Abomey-Calavi', 'commune' => 'Abomey-Calavi', 'quartier' => 'Université', 'address' => 'Université d’Abomey-Calavi, Abomey-Calavi', 'postal_code' => 'BJ-02 BP'],
        ],
        'TG' => [
            ['city' => 'Lomé', 'commune' => 'Centre-ville', 'quartier' => 'Indépendance', 'address' => 'Boulevard du 13 Janvier, Lomé', 'postal_code' => 'TG-01 BP'],
            ['city' => 'Lomé', 'commune' => 'Kodjoviakopé', 'quartier' => 'Frontière', 'address' => 'Kodjoviakopé, Lomé', 'postal_code' => 'TG-02 BP'],
            ['city' => 'Lomé', 'commune' => 'Agoè', 'quartier' => 'Agoè', 'address' => 'Agoè, Lomé', 'postal_code' => 'TG-03 BP'],
        ],
        'MA' => [
            ['city' => 'Casablanca', 'commune' => 'Anfa', 'quartier' => 'Corniche', 'address' => 'Boulevard de la Corniche, Ain Diab, Casablanca', 'postal_code' => 'MA-20000'],
            ['city' => 'Rabat', 'commune' => 'Agdal', 'quartier' => 'Agdal', 'address' => 'Avenue Fal Ould Oumeir, Agdal, Rabat', 'postal_code' => 'MA-10090'],
            ['city' => 'Marrakech', 'commune' => 'Guéliz', 'quartier' => 'Guéliz', 'address' => 'Avenue Mohammed V, Guéliz, Marrakech', 'postal_code' => 'MA-40000'],
        ],
        'ZA' => [
            ['city' => 'Cape Town', 'commune' => 'Sea Point', 'quartier' => 'Sea Point', 'address' => 'Beach Road, Sea Point, Cape Town', 'postal_code' => 'ZA-8005'],
            ['city' => 'Johannesburg', 'commune' => 'Sandton', 'quartier' => 'Sandton', 'address' => 'Nelson Mandela Square, Sandton, Johannesburg', 'postal_code' => 'ZA-2196'],
            ['city' => 'Durban', 'commune' => 'Umhlanga', 'quartier' => 'Umhlanga Rocks', 'address' => 'Umhlanga Rocks Drive, Umhlanga, Durban', 'postal_code' => 'ZA-4319'],
        ],
        'KE' => [
            ['city' => 'Nairobi', 'commune' => 'Westlands', 'quartier' => 'Westlands', 'address' => 'Waiyaki Way, Westlands, Nairobi', 'postal_code' => 'KE-00800'],
            ['city' => 'Nairobi', 'commune' => 'Kilimani', 'quartier' => 'Kilimani', 'address' => 'Ngong Road, Kilimani, Nairobi', 'postal_code' => 'KE-00100'],
            ['city' => 'Mombasa', 'commune' => 'Nyali', 'quartier' => 'Nyali', 'address' => 'Nyali Road, Mombasa', 'postal_code' => 'KE-80100'],
        ],
        'RW' => [
            ['city' => 'Kigali', 'commune' => 'Nyarutarama', 'quartier' => 'Nyarutarama', 'address' => 'KG 9 Avenue, Nyarutarama, Kigali', 'postal_code' => 'RW-00000'],
            ['city' => 'Kigali', 'commune' => 'Kacyiru', 'quartier' => 'Kacyiru', 'address' => 'KG 7 Avenue, Kacyiru, Kigali', 'postal_code' => 'RW-00000'],
            ['city' => 'Kigali', 'commune' => 'Remera', 'quartier' => 'Remera', 'address' => 'Amahoro Stadium, Remera, Kigali', 'postal_code' => 'RW-00000'],
        ],
        'TZ' => [
            ['city' => 'Dar es Salaam', 'commune' => 'Masaki', 'quartier' => 'Masaki', 'address' => 'Haile Selassie Road, Masaki, Dar es Salaam', 'postal_code' => 'TZ-14111'],
            ['city' => 'Dar es Salaam', 'commune' => 'Oyster Bay', 'quartier' => 'Oyster Bay', 'address' => 'Toure Drive, Oyster Bay, Dar es Salaam', 'postal_code' => 'TZ-14111'],
            ['city' => 'Arusha', 'commune' => 'Central', 'quartier' => 'Clock Tower', 'address' => 'Clock Tower, Arusha', 'postal_code' => 'TZ-23100'],
        ],
        'UG' => [
            ['city' => 'Kampala', 'commune' => 'Nakasero', 'quartier' => 'Nakasero', 'address' => 'Kampala Road, Nakasero, Kampala', 'postal_code' => 'UG-00256'],
            ['city' => 'Kampala', 'commune' => 'Kololo', 'quartier' => 'Kololo', 'address' => 'Acacia Avenue, Kololo, Kampala', 'postal_code' => 'UG-00256'],
            ['city' => 'Entebbe', 'commune' => 'Entebbe', 'quartier' => 'Airport', 'address' => 'Entebbe International Airport Road, Entebbe', 'postal_code' => 'UG-00256'],
        ],
        'AO' => [
            ['city' => 'Luanda', 'commune' => 'Ingombota', 'quartier' => 'Marginal', 'address' => 'Avenida 4 de Fevereiro, Luanda', 'postal_code' => 'AO-0000'],
            ['city' => 'Luanda', 'commune' => 'Talatona', 'quartier' => 'Talatona', 'address' => 'Belas Shopping, Talatona, Luanda', 'postal_code' => 'AO-0000'],
        ],
        'BI' => [
            ['city' => 'Bujumbura', 'commune' => 'Rohero', 'quartier' => 'Centre-ville', 'address' => 'Avenue de l’Indépendance, Bujumbura', 'postal_code' => 'BI-0000'],
            ['city' => 'Bujumbura', 'commune' => 'Kiriri', 'quartier' => 'Kiriri', 'address' => 'Kiriri, Bujumbura', 'postal_code' => 'BI-0000'],
        ],
        'BW' => [
            ['city' => 'Gaborone', 'commune' => 'CBD', 'quartier' => 'Central Business District', 'address' => 'Masa Square, Gaborone CBD, Gaborone', 'postal_code' => 'BW-0000'],
            ['city' => 'Gaborone', 'commune' => 'Phakalane', 'quartier' => 'Phakalane', 'address' => 'Phakalane Golf Estate, Gaborone', 'postal_code' => 'BW-0000'],
        ],
        'CD' => [
            ['city' => 'Kinshasa', 'commune' => 'Gombe', 'quartier' => 'Gombe', 'address' => 'Boulevard du 30 Juin, Gombe, Kinshasa', 'postal_code' => 'CD-0000'],
            ['city' => 'Lubumbashi', 'commune' => 'Lubumbashi', 'quartier' => 'Centre-ville', 'address' => 'Avenue Kasa-Vubu, Lubumbashi', 'postal_code' => 'CD-0000'],
        ],
        'CF' => [
            ['city' => 'Bangui', 'commune' => 'Centre-ville', 'quartier' => 'Place de la République', 'address' => 'Place de la République, Bangui', 'postal_code' => 'CF-0000'],
            ['city' => 'Bangui', 'commune' => 'Centre-ville', 'quartier' => 'Avenue Boganda', 'address' => 'Avenue Barthélémy Boganda, Bangui', 'postal_code' => 'CF-0000'],
        ],
        'CG' => [
            ['city' => 'Brazzaville', 'commune' => 'Centre-ville', 'quartier' => 'Plateau', 'address' => 'Avenue de la Paix, Brazzaville', 'postal_code' => 'CG-0000'],
            ['city' => 'Pointe-Noire', 'commune' => 'Centre-ville', 'quartier' => 'Front de mer', 'address' => 'Avenue Charles de Gaulle, Pointe-Noire', 'postal_code' => 'CG-0000'],
        ],
        'CV' => [
            ['city' => 'Praia', 'commune' => 'Plateau', 'quartier' => 'Plateau', 'address' => 'Avenida Cidade de Lisboa, Praia', 'postal_code' => 'CV-0000'],
            ['city' => 'Mindelo', 'commune' => 'Mindelo', 'quartier' => 'Centre-ville', 'address' => 'Rua de Lisboa, Mindelo', 'postal_code' => 'CV-0000'],
        ],
        'DJ' => [
            ['city' => 'Djibouti', 'commune' => 'Plateau du Serpent', 'quartier' => 'Héron', 'address' => 'Boulevard de la République, Djibouti', 'postal_code' => 'DJ-0000'],
            ['city' => 'Djibouti', 'commune' => 'Héron', 'quartier' => 'Héron', 'address' => 'Place Menelik, Djibouti', 'postal_code' => 'DJ-0000'],
        ],
        'DZ' => [
            ['city' => 'Alger', 'commune' => 'Hydra', 'quartier' => 'Hydra', 'address' => 'Rue Didouche Mourad, Alger', 'postal_code' => 'DZ-16000'],
            ['city' => 'Oran', 'commune' => 'Centre-ville', 'quartier' => 'Front de mer', 'address' => 'Boulevard de l’ALN, Oran', 'postal_code' => 'DZ-31000'],
        ],
        'EG' => [
            ['city' => 'Le Caire', 'commune' => 'Zamalek', 'quartier' => 'Zamalek', 'address' => 'Gezira Street, Zamalek, Cairo', 'postal_code' => 'EG-11211'],
            ['city' => 'Le Caire', 'commune' => 'Maadi', 'quartier' => 'Maadi', 'address' => 'Road 9, Maadi, Cairo', 'postal_code' => 'EG-11728'],
        ],
        'ER' => [
            ['city' => 'Asmara', 'commune' => 'Centre-ville', 'quartier' => 'Harnet', 'address' => 'Harnet Avenue, Asmara', 'postal_code' => 'ER-0000'],
            ['city' => 'Asmara', 'commune' => 'Centre-ville', 'quartier' => 'Liberation Avenue', 'address' => 'Liberation Avenue, Asmara', 'postal_code' => 'ER-0000'],
        ],
        'ET' => [
            ['city' => 'Addis-Abeba', 'commune' => 'Bole', 'quartier' => 'Bole', 'address' => 'Africa Avenue, Bole, Addis Ababa', 'postal_code' => 'ET-1000'],
            ['city' => 'Addis-Abeba', 'commune' => 'Kazanchis', 'quartier' => 'Kazanchis', 'address' => 'Kazanchis, Addis Ababa', 'postal_code' => 'ET-1000'],
        ],
        'GA' => [
            ['city' => 'Libreville', 'commune' => 'Centre-ville', 'quartier' => 'Bord de mer', 'address' => 'Boulevard de l’Indépendance, Libreville', 'postal_code' => 'GA-0000'],
            ['city' => 'Libreville', 'commune' => 'Akanda', 'quartier' => 'Akanda', 'address' => 'Akanda, Libreville', 'postal_code' => 'GA-0000'],
        ],
        'GM' => [
            ['city' => 'Banjul', 'commune' => 'Banjul', 'quartier' => 'Independence Drive', 'address' => 'Independence Drive, Banjul', 'postal_code' => 'GM-0000'],
            ['city' => 'Serrekunda', 'commune' => 'Kololi', 'quartier' => 'Senegambia', 'address' => 'Senegambia Road, Kololi, Serrekunda', 'postal_code' => 'GM-0000'],
        ],
        'GN' => [
            ['city' => 'Conakry', 'commune' => 'Kaloum', 'quartier' => 'Centre-ville', 'address' => 'Avenue de la République, Kaloum, Conakry', 'postal_code' => 'GN-0000'],
            ['city' => 'Conakry', 'commune' => 'Dixinn', 'quartier' => 'Dixinn', 'address' => 'Dixinn, Conakry', 'postal_code' => 'GN-0000'],
        ],
        'GQ' => [
            ['city' => 'Malabo', 'commune' => 'Malabo', 'quartier' => 'Centre-ville', 'address' => 'Avenida de la Independencia, Malabo', 'postal_code' => 'GQ-0000'],
            ['city' => 'Bata', 'commune' => 'Bata', 'quartier' => 'Front de mer', 'address' => 'Paseo Maritimo, Bata', 'postal_code' => 'GQ-0000'],
        ],
        'GW' => [
            ['city' => 'Bissau', 'commune' => 'Bissau', 'quartier' => 'Centre-ville', 'address' => 'Avenida Amílcar Cabral, Bissau', 'postal_code' => 'GW-0000'],
            ['city' => 'Bissau', 'commune' => 'Bissau', 'quartier' => 'Praça dos Heróis', 'address' => 'Praça dos Heróis Nacionais, Bissau', 'postal_code' => 'GW-0000'],
        ],
        'KM' => [
            ['city' => 'Moroni', 'commune' => 'Moroni', 'quartier' => 'Corniche', 'address' => 'Route de la Corniche, Moroni', 'postal_code' => 'KM-0000'],
            ['city' => 'Moroni', 'commune' => 'Itsandra', 'quartier' => 'Itsandra', 'address' => 'Itsandra, Moroni', 'postal_code' => 'KM-0000'],
        ],
        'LR' => [
            ['city' => 'Monrovia', 'commune' => 'Mamba Point', 'quartier' => 'Mamba Point', 'address' => 'United Nations Drive, Mamba Point, Monrovia', 'postal_code' => 'LR-0000'],
            ['city' => 'Monrovia', 'commune' => 'Sinkor', 'quartier' => 'Sinkor', 'address' => 'Tubman Boulevard, Sinkor, Monrovia', 'postal_code' => 'LR-0000'],
        ],
        'LS' => [
            ['city' => 'Maseru', 'commune' => 'Maseru', 'quartier' => 'Centre-ville', 'address' => 'Kingsway Road, Maseru', 'postal_code' => 'LS-100'],
            ['city' => 'Maseru', 'commune' => 'Thetsane', 'quartier' => 'Thetsane', 'address' => 'Thetsane Industrial Area, Maseru', 'postal_code' => 'LS-100'],
        ],
        'LY' => [
            ['city' => 'Tripoli', 'commune' => 'Tripoli', 'quartier' => 'Centre-ville', 'address' => 'Martyrs Square, Tripoli, Libya', 'postal_code' => 'LY-0000'],
            ['city' => 'Benghazi', 'commune' => 'Benghazi', 'quartier' => 'Centre-ville', 'address' => 'Gamal Abdel Nasser Street, Benghazi', 'postal_code' => 'LY-0000'],
        ],
        'MG' => [
            ['city' => 'Antananarivo', 'commune' => 'Analakely', 'quartier' => 'Analakely', 'address' => 'Avenue de l’Indépendance, Analakely, Antananarivo', 'postal_code' => 'MG-101'],
            ['city' => 'Antananarivo', 'commune' => 'Ivandry', 'quartier' => 'Ivandry', 'address' => 'Ivandry, Antananarivo', 'postal_code' => 'MG-101'],
        ],
        'ML' => [
            ['city' => 'Bamako', 'commune' => 'ACI 2000', 'quartier' => 'ACI 2000', 'address' => 'Avenue de l’OUA, ACI 2000, Bamako', 'postal_code' => 'ML-0000'],
            ['city' => 'Bamako', 'commune' => 'Hamdallaye', 'quartier' => 'Hamdallaye', 'address' => 'Hamdallaye ACI, Bamako', 'postal_code' => 'ML-0000'],
        ],
        'MR' => [
            ['city' => 'Nouakchott', 'commune' => 'Tevragh Zeina', 'quartier' => 'Tevragh Zeina', 'address' => 'Avenue Moktar Ould Daddah, Nouakchott', 'postal_code' => 'MR-0000'],
            ['city' => 'Nouakchott', 'commune' => 'Ksar', 'quartier' => 'Ksar', 'address' => 'Ksar, Nouakchott', 'postal_code' => 'MR-0000'],
        ],
        'MU' => [
            ['city' => 'Port-Louis', 'commune' => 'Caudan', 'quartier' => 'Waterfront', 'address' => 'Caudan Waterfront, Port Louis', 'postal_code' => 'MU-11307'],
            ['city' => 'Grand Baie', 'commune' => 'Grand Baie', 'quartier' => 'Coastal Road', 'address' => 'Coastal Road, Grand Baie, Mauritius', 'postal_code' => 'MU-30510'],
        ],
        'MW' => [
            ['city' => 'Lilongwe', 'commune' => 'City Centre', 'quartier' => 'Area 13', 'address' => 'City Centre, Lilongwe', 'postal_code' => 'MW-0000'],
            ['city' => 'Blantyre', 'commune' => 'Blantyre', 'quartier' => 'Victoria Avenue', 'address' => 'Victoria Avenue, Blantyre', 'postal_code' => 'MW-0000'],
        ],
        'MZ' => [
            ['city' => 'Maputo', 'commune' => 'Polana', 'quartier' => 'Polana', 'address' => 'Avenida Julius Nyerere, Polana, Maputo', 'postal_code' => 'MZ-1100'],
            ['city' => 'Maputo', 'commune' => 'Baixa', 'quartier' => 'Centre-ville', 'address' => 'Avenida 25 de Setembro, Maputo', 'postal_code' => 'MZ-1100'],
        ],
        'NA' => [
            ['city' => 'Windhoek', 'commune' => 'CBD', 'quartier' => 'Independence Avenue', 'address' => 'Independence Avenue, Windhoek', 'postal_code' => 'NA-10005'],
            ['city' => 'Windhoek', 'commune' => 'Klein Windhoek', 'quartier' => 'Klein Windhoek', 'address' => 'Nelson Mandela Avenue, Windhoek', 'postal_code' => 'NA-10005'],
        ],
        'NE' => [
            ['city' => 'Niamey', 'commune' => 'Plateau', 'quartier' => 'Plateau', 'address' => 'Avenue de la République, Niamey', 'postal_code' => 'NE-0000'],
            ['city' => 'Niamey', 'commune' => 'Koira Kano', 'quartier' => 'Koira Kano', 'address' => 'Koira Kano, Niamey', 'postal_code' => 'NE-0000'],
        ],
        'SC' => [
            ['city' => 'Victoria', 'commune' => 'Victoria', 'quartier' => 'Centre-ville', 'address' => 'Independence Avenue, Victoria, Seychelles', 'postal_code' => 'SC-0000'],
            ['city' => 'Beau Vallon', 'commune' => 'Beau Vallon', 'quartier' => 'Beach', 'address' => 'Beau Vallon Beach, Seychelles', 'postal_code' => 'SC-0000'],
        ],
        'SD' => [
            ['city' => 'Khartoum', 'commune' => 'Khartoum', 'quartier' => 'Nile Street', 'address' => 'Nile Street, Khartoum', 'postal_code' => 'SD-11111'],
            ['city' => 'Khartoum', 'commune' => 'Al Amarat', 'quartier' => 'Al Amarat', 'address' => 'Africa Street, Al Amarat, Khartoum', 'postal_code' => 'SD-11111'],
        ],
        'SL' => [
            ['city' => 'Freetown', 'commune' => 'Aberdeen', 'quartier' => 'Aberdeen', 'address' => 'Lumley Beach Road, Aberdeen, Freetown', 'postal_code' => 'SL-0000'],
            ['city' => 'Freetown', 'commune' => 'Central', 'quartier' => 'Cotton Tree', 'address' => 'Cotton Tree, Freetown', 'postal_code' => 'SL-0000'],
        ],
        'SO' => [
            ['city' => 'Mogadiscio', 'commune' => 'Hamar Weyne', 'quartier' => 'Centre-ville', 'address' => 'Maka Al Mukarama Road, Mogadishu', 'postal_code' => 'SO-0000'],
            ['city' => 'Mogadiscio', 'commune' => 'Waberi', 'quartier' => 'Airport Area', 'address' => 'Aden Adde International Airport Road, Mogadishu', 'postal_code' => 'SO-0000'],
        ],
        'SS' => [
            ['city' => 'Juba', 'commune' => 'Juba', 'quartier' => 'Airport Road', 'address' => 'Airport Road, Juba', 'postal_code' => 'SS-0000'],
            ['city' => 'Juba', 'commune' => 'Hai Cinema', 'quartier' => 'Hai Cinema', 'address' => 'Hai Cinema, Juba', 'postal_code' => 'SS-0000'],
        ],
        'ST' => [
            ['city' => 'São Tomé', 'commune' => 'São Tomé', 'quartier' => 'Centre-ville', 'address' => 'Avenida Marginal 12 de Julho, São Tomé', 'postal_code' => 'ST-0000'],
            ['city' => 'São Tomé', 'commune' => 'São Tomé', 'quartier' => 'Independência', 'address' => 'Praça da Independência, São Tomé', 'postal_code' => 'ST-0000'],
        ],
        'SZ' => [
            ['city' => 'Mbabane', 'commune' => 'Mbabane', 'quartier' => 'Centre-ville', 'address' => 'Gwamile Street, Mbabane', 'postal_code' => 'SZ-H100'],
            ['city' => 'Ezulwini', 'commune' => 'Ezulwini', 'quartier' => 'Valley', 'address' => 'Ezulwini Valley, Eswatini', 'postal_code' => 'SZ-H106'],
        ],
        'TD' => [
            ['city' => 'N’Djamena', 'commune' => 'Centre-ville', 'quartier' => 'Avenue Charles de Gaulle', 'address' => 'Avenue Charles de Gaulle, N’Djamena', 'postal_code' => 'TD-0000'],
            ['city' => 'N’Djamena', 'commune' => 'Klemat', 'quartier' => 'Klemat', 'address' => 'Quartier Klemat, N’Djamena', 'postal_code' => 'TD-0000'],
        ],
        'TN' => [
            ['city' => 'Tunis', 'commune' => 'Centre-ville', 'quartier' => 'Bourguiba', 'address' => 'Avenue Habib Bourguiba, Tunis', 'postal_code' => 'TN-1000'],
            ['city' => 'Tunis', 'commune' => 'Lac 2', 'quartier' => 'Lac 2', 'address' => 'Rue du Lac Biwa, Les Berges du Lac 2, Tunis', 'postal_code' => 'TN-1053'],
        ],
        'ZM' => [
            ['city' => 'Lusaka', 'commune' => 'Lusaka', 'quartier' => 'Cairo Road', 'address' => 'Cairo Road, Lusaka', 'postal_code' => 'ZM-10101'],
            ['city' => 'Lusaka', 'commune' => 'Rhodes Park', 'quartier' => 'Rhodes Park', 'address' => 'Great East Road, Lusaka', 'postal_code' => 'ZM-10101'],
        ],
        'ZW' => [
            ['city' => 'Harare', 'commune' => 'Harare', 'quartier' => 'CBD', 'address' => 'Samora Machel Avenue, Harare', 'postal_code' => 'ZW-0000'],
            ['city' => 'Harare', 'commune' => 'Avondale', 'quartier' => 'Avondale', 'address' => 'King George Road, Avondale, Harare', 'postal_code' => 'ZW-0000'],
        ],
    ];

    private const LOCATIONS = [
        'CI' => ['cities' => ['Abidjan', 'Yamoussoukro', 'Bouaké', 'San-Pédro'], 'districts' => ['Centre-ville', 'Zone résidentielle'], 'street' => 'Boulevard Lagunaire', 'areas' => ['Abidjan' => ['Cocody', 'Riviera', 'Marcory', 'Plateau', 'Yopougon'], 'Yamoussoukro' => ['Assabou', 'Habitat', 'Morofé', '220 Logements'], 'Bouaké' => ['Air France', 'Kennedy', 'Nimbo', 'Belleville'], 'San-Pédro' => ['Bardot', 'Lac', 'Séwéké', 'Poro']]],
        'SN' => ['cities' => ['Dakar', 'Thiès', 'Saly', 'Saint-Louis'], 'districts' => ['Centre-ville', 'Zone résidentielle'], 'street' => 'Corniche Ouest', 'areas' => ['Dakar' => ['Almadies', 'Mermoz', 'Plateau', 'Ngor', 'Ouakam'], 'Thiès' => ['Grand Standing', 'Mbour 1', 'Randoulène'], 'Saly' => ['Saly Portudal', 'Saly Niakh Niakhal', 'Saly Joseph'], 'Saint-Louis' => ['Sor', 'Guet Ndar', 'Île Nord']]],
        'ML' => ['cities' => ['Bamako', 'Ségou', 'Sikasso', 'Kayes'], 'districts' => ['ACI 2000', 'Hamdallaye', 'Badalabougou', 'Sotuba'], 'street' => 'Avenue de l’OUA'],
        'BF' => ['cities' => ['Ouagadougou', 'Bobo-Dioulasso', 'Koudougou'], 'districts' => ['Ouaga 2000', 'Zone du Bois', 'Koulouba', 'Patte d’Oie'], 'street' => 'Avenue Kwame Nkrumah'],
        'BJ' => ['cities' => ['Cotonou', 'Porto-Novo', 'Abomey-Calavi'], 'districts' => ['Haie Vive', 'Akpakpa', 'Fidjrossè', 'Ganhi'], 'street' => 'Boulevard de la Marina'],
        'TG' => ['cities' => ['Lomé', 'Kara', 'Sokodé'], 'districts' => ['Kodjoviakopé', 'Agoè', 'Bè', 'Adidogomé'], 'street' => 'Boulevard du 13 Janvier'],
        'GH' => ['cities' => ['Accra', 'Kumasi', 'Tema', 'Takoradi'], 'districts' => ['Centre-ville', 'Residential Area'], 'street' => 'Independence Avenue', 'areas' => ['Accra' => ['Airport Residential', 'Osu', 'Cantonments', 'East Legon'], 'Kumasi' => ['Nhyiaeso', 'Asokwa', 'Ahodwo'], 'Tema' => ['Community 1', 'Community 25', 'Sakumono'], 'Takoradi' => ['Beach Road', 'Airport Ridge', 'Anaji']]],
        'NG' => ['cities' => ['Lagos', 'Abuja', 'Port Harcourt', 'Ibadan'], 'districts' => ['Centre-ville', 'Residential Area'], 'street' => 'Ahmadu Bello Way', 'areas' => ['Lagos' => ['Victoria Island', 'Lekki', 'Ikoyi', 'Ikeja'], 'Abuja' => ['Maitama', 'Wuse 2', 'Asokoro', 'Garki'], 'Port Harcourt' => ['Old GRA', 'Trans Amadi', 'D-Line'], 'Ibadan' => ['Bodija', 'Jericho', 'Agodi']]],
        'CM' => ['cities' => ['Douala', 'Yaoundé', 'Kribi', 'Bafoussam'], 'districts' => ['Centre-ville', 'Zone résidentielle'], 'street' => 'Boulevard de la Liberté', 'areas' => ['Douala' => ['Bonapriso', 'Akwa', 'Makepe', 'Bonamoussadi'], 'Yaoundé' => ['Bastos', 'Mvan', 'Odza', 'Mvog-Ada'], 'Kribi' => ['Mboa Manga', 'Nziou', 'Bongandoué'], 'Bafoussam' => ['Tamja', 'Banengo', 'Djeleng']]],
        'GA' => ['cities' => ['Libreville', 'Port-Gentil', 'Franceville'], 'districts' => ['Louis', 'Glass', 'Akanda', 'Batterie IV'], 'street' => 'Boulevard Triomphal'],
        'MA' => ['cities' => ['Casablanca', 'Rabat', 'Marrakech', 'Tanger'], 'districts' => ['Anfa', 'Agdal', 'Guéliz', 'Souissi'], 'street' => 'Boulevard Mohammed V'],
        'TN' => ['cities' => ['Tunis', 'Sousse', 'Sfax', 'Hammamet'], 'districts' => ['Lac 2', 'Marsa', 'Carthage', 'El Menzah'], 'street' => 'Avenue Habib Bourguiba'],
        'ZA' => ['cities' => ['Johannesburg', 'Cape Town', 'Durban', 'Pretoria'], 'districts' => ['Sandton', 'Sea Point', 'Umhlanga', 'Rosebank'], 'street' => 'Nelson Mandela Boulevard'],
        'DZ' => ['cities' => ['Alger', 'Oran', 'Constantine'], 'districts' => ['Hydra', 'Bab Ezzouar', 'El Madania'], 'street' => 'Boulevard Didouche Mourad'],
        'AO' => ['cities' => ['Luanda', 'Benguela', 'Lubango'], 'districts' => ['Talatona', 'Miramar', 'Maianga'], 'street' => 'Avenida 4 de Fevereiro'],
        'BW' => ['cities' => ['Gaborone', 'Francistown', 'Maun'], 'districts' => ['CBD', 'Phakalane', 'Broadhurst'], 'street' => 'Nelson Mandela Drive'],
        'BI' => ['cities' => ['Bujumbura', 'Gitega', 'Ngozi'], 'districts' => ['Kiriri', 'Rohero', 'Kinindo'], 'street' => 'Avenue de l’Indépendance'],
        'CV' => ['cities' => ['Praia', 'Mindelo', 'Santa Maria'], 'districts' => ['Palmarejo', 'Plateau', 'Achada Santo António'], 'street' => 'Avenida Cidade de Lisboa'],
        'KM' => ['cities' => ['Moroni', 'Mutsamudu', 'Fomboni'], 'districts' => ['Itsandra', 'Badjanani', 'Voidjou'], 'street' => 'Route de la Corniche'],
        'CG' => ['cities' => ['Brazzaville', 'Pointe-Noire', 'Dolisie'], 'districts' => ['Centre-ville', 'Bacongo', 'Moungali'], 'street' => 'Avenue de la Paix'],
        'CD' => ['cities' => ['Kinshasa', 'Lubumbashi', 'Goma'], 'districts' => ['Gombe', 'Limete', 'Ngaliema'], 'street' => 'Boulevard du 30 Juin'],
        'DJ' => ['cities' => ['Djibouti', 'Ali Sabieh', 'Tadjourah'], 'districts' => ['Héron', 'Plateau du Serpent', 'Ambouli'], 'street' => 'Boulevard de la République'],
        'EG' => ['cities' => ['Le Caire', 'Alexandrie', 'Gizeh'], 'districts' => ['Zamalek', 'Maadi', 'Heliopolis'], 'street' => 'Corniche El Nile'],
        'ET' => ['cities' => ['Addis-Abeba', 'Dire Dawa', 'Bahir Dar'], 'districts' => ['Bole', 'Kazanchis', 'CMC'], 'street' => 'Africa Avenue'],
        'KE' => ['cities' => ['Nairobi', 'Mombasa', 'Kisumu'], 'districts' => ['Westlands', 'Karen', 'Kilimani'], 'street' => 'Mombasa Road'],
        'RW' => ['cities' => ['Kigali', 'Musanze', 'Rubavu'], 'districts' => ['Kacyiru', 'Nyarutarama', 'Remera'], 'street' => 'KN 3 Road'],
        'TZ' => ['cities' => ['Dar es Salaam', 'Arusha', 'Dodoma'], 'districts' => ['Masaki', 'Oyster Bay', 'Mikocheni'], 'street' => 'Ali Hassan Mwinyi Road'],
        'UG' => ['cities' => ['Kampala', 'Entebbe', 'Jinja'], 'districts' => ['Kololo', 'Nakasero', 'Ntinda'], 'street' => 'Kampala Road'],
    ];

    public static function forCountryId(?int $countryId): array
    {
        $country = $countryId ? Country::find($countryId) : null;
        $code = $country?->code;

        if ($code && isset(self::REAL_LOCATIONS[$code])) {
            $place = fake()->randomElement(self::REAL_LOCATIONS[$code]);

            return [
                'country_code' => $code,
                'country_name' => $country?->name,
                'city' => $place['city'],
                'commune' => $place['commune'],
                'quartier' => $place['quartier'],
                'location' => "{$place['quartier']}, {$place['city']}",
                'address' => $place['address'],
                'postal_code' => $place['postal_code'],
            ];
        }

        $data = self::LOCATIONS[$code] ?? self::defaultLocation($country?->name ?? 'Afrique');
        $city = fake()->randomElement($data['cities']);
        $district = fake()->randomElement($data['areas'][$city] ?? $data['districts']);
        $number = fake()->numberBetween(1, 240);

        return [
            'country_code' => $code,
            'country_name' => $country?->name,
            'city' => $city,
            'commune' => $district,
            'quartier' => $district,
            'location' => "{$district}, {$city}",
            'address' => "{$number}, {$data['street']}, {$district}, {$city}",
            'postal_code' => strtoupper(($code ?: 'AF') . '-' . fake()->numberBetween(1000, 9999)),
        ];
    }

    private static function defaultLocation(string $countryName): array
    {
        return [
            'cities' => ["Capitale {$countryName}", "Centre économique {$countryName}", "Zone résidentielle {$countryName}"],
            'districts' => ['Centre-ville', 'Quartier administratif', 'Zone résidentielle', 'Corniche'],
            'street' => 'Avenue Principale',
        ];
    }
}
