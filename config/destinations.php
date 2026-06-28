<?php
/**
 * Destination data.
 *
 * LOCAL_DESTINATIONS  – map of country → popular domestic places.
 * INTERNATIONAL_DESTINATIONS – grouped list of popular world destinations.
 */

define('LOCAL_DESTINATIONS', [
    'Philippines' => [
        'Batanes', 'Batangas', 'Bohol', 'Boracay', 'Camiguin', 'Cebu City',
        'Coron, Palawan', 'Davao City', 'Dumaguete', 'El Nido, Palawan',
        'General Luna, Siargao', 'Iloilo City', 'La Union', 'Legazpi, Bicol',
        'Malapascua Island', 'Naga City', 'Pagudpud', 'Puerto Princesa',
        'Sagada', 'Siargao Island', 'Tagaytay', 'Vigan, Ilocos Sur',
        'Zamboanga City',
    ],
    'Indonesia' => [
        'Bali', 'Bandung', 'Batam', 'Belitung', 'Bintan', 'Flores',
        'Gili Islands', 'Jakarta', 'Yogyakarta', 'Komodo Island',
        'Labuan Bajo', 'Lombok', 'Makassar', 'Malang', 'Medan',
        'Raja Ampat', 'Surabaya', 'Toraja', 'Wakatobi',
    ],
    'Thailand' => [
        'Bangkok', 'Chiang Mai', 'Chiang Rai', 'Hua Hin', 'Kanchanaburi',
        'Koh Kood', 'Koh Lanta', 'Koh Phangan', 'Koh Samui', 'Koh Tao',
        'Krabi', 'Pai', 'Pattaya', 'Phuket', 'Sukhothai',
    ],
    'Vietnam' => [
        'Da Lat', 'Da Nang', 'Ha Long Bay', 'Hanoi', 'Ho Chi Minh City',
        'Hoi An', 'Hue', 'Nha Trang', 'Phu Quoc', 'Quy Nhon', 'Sa Pa',
    ],
    'Malaysia' => [
        'Cameron Highlands', 'George Town, Penang', 'Ipoh', 'Johor Bahru',
        'Kota Kinabalu', 'Kuala Lumpur', 'Kuching, Sarawak',
        'Langkawi', 'Malacca', 'Tioman Island',
    ],
    'Singapore' => [
        'Clarke Quay', 'Gardens by the Bay', 'Little India',
        'Marina Bay Sands', 'Orchard Road', 'Sentosa Island',
    ],
    'Japan' => [
        'Fukuoka', 'Hakone', 'Hiroshima', 'Hokkaido (Sapporo)', 'Kamakura',
        'Kanazawa', 'Kyoto', 'Miyajima', 'Nagasaki', 'Nagoya',
        'Nara', 'Nikko', 'Okinawa', 'Osaka', 'Tokyo',
    ],
    'South Korea' => [
        'Busan', 'Gyeongju', 'Incheon', 'Jeju Island', 'Seoul',
    ],
    'China' => [
        'Beijing', 'Chengdu', 'Guilin', 'Hangzhou', 'Lhasa',
        'Shanghai', 'Shenzhen', "Xi'an", 'Zhangjiajie',
    ],
    'India' => [
        'Agra', 'Bangalore', 'Chennai', 'Delhi', 'Goa',
        'Jaipur', 'Kolkata', 'Mumbai', 'Mysore', 'Rishikesh',
        'Udaipur', 'Varanasi',
    ],
    'Australia' => [
        'Adelaide', 'Brisbane', 'Cairns', 'Darwin', 'Gold Coast',
        'Melbourne', 'Perth', 'Sydney', 'Uluru',
    ],
    'New Zealand' => [
        'Auckland', 'Christchurch', 'Dunedin', 'Queenstown',
        'Rotorua', 'Taupo', 'Wellington',
    ],
    'United States' => [
        'Boston', 'Chicago', 'Honolulu', 'Las Vegas', 'Los Angeles',
        'Miami', 'Nashville', 'New Orleans', 'New York City',
        'Portland', 'San Francisco', 'Seattle', 'Washington D.C.',
    ],
    'Canada' => [
        'Banff', 'Calgary', 'Montreal', 'Ottawa', 'Quebec City',
        'Toronto', 'Vancouver', 'Victoria', 'Whistler',
    ],
    'United Kingdom' => [
        'Bath', 'Birmingham', 'Brighton', 'Cambridge', 'Edinburgh',
        'Glasgow', 'Liverpool', 'London', 'Manchester', 'Oxford', 'York',
    ],
    'France' => [
        'Bordeaux', 'Chamonix', 'Lyon', 'Marseille', 'Mont Saint-Michel',
        'Nice', 'Paris', 'Strasbourg', 'Toulouse',
    ],
    'Germany' => [
        'Berlin', 'Cologne', 'Dresden', 'Frankfurt', 'Hamburg',
        'Heidelberg', 'Munich', 'Nuremberg',
    ],
    'Italy' => [
        'Amalfi Coast', 'Bologna', 'Cinque Terre', 'Florence',
        'Lake Como', 'Milan', 'Naples', 'Rome', 'Sicily',
        'Turin', 'Venice', 'Verona',
    ],
    'Spain' => [
        'Barcelona', 'Bilbao', 'Granada', 'Ibiza', 'Madrid',
        'Malaga', 'Seville', 'Valencia',
    ],
    'Greece' => [
        'Athens', 'Corfu', 'Crete', 'Mykonos', 'Rhodes',
        'Santorini', 'Thessaloniki',
    ],
    'Turkey' => [
        'Antalya', 'Bodrum', 'Cappadocia', 'Ephesus', 'Istanbul',
        'Izmir', 'Pamukkale',
    ],
    'Brazil' => [
        'Bahia', 'Bonito', 'Fernando de Noronha', 'Florianopolis',
        'Fortaleza', 'Foz do Iguazu', 'Manaus',
        'Natal', 'Recife', 'Rio de Janeiro', 'Sao Paulo',
    ],
    'Mexico' => [
        'Cancun', 'Guadalajara', 'Guanajuato', 'Mexico City',
        'Oaxaca', 'Puerto Vallarta', 'Tulum',
    ],
    'South Africa' => [
        'Cape Town', 'Durban', 'Garden Route', 'Johannesburg',
        'Kruger National Park', 'Pretoria', 'Stellenbosch',
    ],
    'Egypt' => [
        'Alexandria', 'Aswan', 'Cairo', 'Dahab', 'Hurghada',
        'Luxor', 'Sharm El-Sheikh',
    ],
    'UAE' => [
        'Abu Dhabi', 'Dubai', 'Fujairah', 'Sharjah',
    ],
]);

define('INTERNATIONAL_DESTINATIONS', [
    'Southeast Asia' => [
        'Bali, Indonesia', 'Bangkok, Thailand', 'Cebu, Philippines',
        'Chiang Mai, Thailand', 'Da Nang, Vietnam', 'Ha Long Bay, Vietnam',
        'Ho Chi Minh City, Vietnam', 'Hoi An, Vietnam', 'Koh Samui, Thailand',
        'Krabi, Thailand', 'Kuala Lumpur, Malaysia', 'Langkawi, Malaysia',
        'Luang Prabang, Laos', 'Manila, Philippines', 'Phnom Penh, Cambodia',
        'Phuket, Thailand', 'Puerto Princesa, Philippines', 'Raja Ampat, Indonesia',
        'Siem Reap, Cambodia', 'Singapore', 'Yangon, Myanmar',
    ],
    'East Asia' => [
        'Beijing, China', 'Busan, South Korea', 'Fukuoka, Japan',
        'Hong Kong', 'Kyoto, Japan', 'Macau',
        'Osaka, Japan', 'Seoul, South Korea', 'Shanghai, China',
        'Taipei, Taiwan', 'Tokyo, Japan',
    ],
    'South Asia & Maldives' => [
        'Colombo, Sri Lanka', 'Delhi, India', 'Goa, India',
        'Jaipur, India', 'Kathmandu, Nepal', 'Maldives', 'Mumbai, India',
    ],
    'Middle East' => [
        'Abu Dhabi, UAE', 'Amman, Jordan', 'Cappadocia, Turkey',
        'Doha, Qatar', 'Dubai, UAE', 'Istanbul, Turkey',
        'Jerusalem, Israel', 'Muscat, Oman', 'Petra, Jordan',
        'Tel Aviv, Israel',
    ],
    'Europe' => [
        'Amsterdam, Netherlands', 'Athens, Greece', 'Barcelona, Spain',
        'Berlin, Germany', 'Budapest, Hungary', 'Copenhagen, Denmark',
        'Dubrovnik, Croatia', 'Edinburgh, UK', 'Florence, Italy',
        'Lisbon, Portugal', 'London, UK', 'Madrid, Spain',
        'Milan, Italy', 'Munich, Germany', 'Mykonos, Greece',
        'Nice, France', 'Paris, France', 'Prague, Czech Republic',
        'Reykjavik, Iceland', 'Rome, Italy', 'Santorini, Greece',
        'Stockholm, Sweden', 'Venice, Italy', 'Vienna, Austria',
        'Zurich, Switzerland',
    ],
    'Americas' => [
        'Buenos Aires, Argentina', 'Cancun, Mexico', 'Cartagena, Colombia',
        'Chicago, USA', 'Cusco, Peru', 'Havana, Cuba',
        'Honolulu, USA', 'Las Vegas, USA', 'Lima, Peru',
        'Los Angeles, USA', 'Machu Picchu, Peru', 'Mexico City, Mexico',
        'Miami, USA', 'Montreal, Canada', 'New Orleans, USA',
        'New York City, USA', 'Rio de Janeiro, Brazil', 'San Francisco, USA',
        'Sao Paulo, Brazil', 'Toronto, Canada', 'Tulum, Mexico',
        'Vancouver, Canada',
    ],
    'Africa' => [
        'Cairo, Egypt', 'Cape Town, South Africa', 'Casablanca, Morocco',
        'Hurghada, Egypt', 'Johannesburg, South Africa', 'Luxor, Egypt',
        'Marrakech, Morocco', 'Nairobi, Kenya', 'Serengeti, Tanzania',
        'Zanzibar, Tanzania',
    ],
    'Oceania' => [
        'Auckland, New Zealand', 'Brisbane, Australia', 'Bora Bora, French Polynesia',
        'Cairns, Australia', 'Fiji', 'Gold Coast, Australia',
        'Melbourne, Australia', 'Queenstown, New Zealand', 'Sydney, Australia',
    ],
]);
