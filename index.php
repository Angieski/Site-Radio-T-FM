<!DOCTYPE html>
<?php

$weather_api_key = '7edb4e0f27d075ce3ca568775e2e4f83';
require_once 'conexaoLocal.php';
$cidades_meteorologicas = [];
if (!empty($radios_map)) {
    foreach ($radios_map as $radio) {
        $cidade = trim($radio['cidade']);
        $cidade = preg_replace('/\s+/', ' ', $cidade); // Remove múltiplos espaços
        if (!empty($cidade) && !in_array($cidade, $cidades_meteorologicas)) {
            $cidades_meteorologicas[] = $cidade;
        }
    }
}

// Função para buscar clima
function getWeatherData($city, $api_key) {
    // Passo 1: Substituir nomes específicos e remover sufixos
    $city = str_replace(
        [
            'Foz do Iguaçu', 
            'Maringá', 
            'Paranaguá',
            'Matinhos - Caioba'  // Nome completo para substituição
        ],
        [
            'Foz do Iguacu', 
            'Maringa', 
            'Paranagua',
            'Matinhos'  // Nome simplificado para a API
        ], 
        $city
    );

    // Passo 2: Remover qualquer sufixo após hífen
    $city = preg_replace('/\s*-\s*.+/', '', $city);
    
    // Passo 3: Normalizar espaços e caracteres especiais
    $city = preg_replace('/\s+/', '_', trim($city));
    
    // Passo 4: Criar nome de arquivo seguro
    $safe_filename = preg_replace('/[^a-zA-Z0-9_]/', '', $city);
    
    // Passo 5: Codificar para URL
    $city_encoded = urlencode(str_replace('_', ' ', $city));
    
    $cache_file = "weather_cache/{$safe_filename}_weather.json";
    
    // Restante do código mantido...
    $url = "http://api.openweathermap.org/data/2.5/weather?q={$city_encoded},BR&units=metric&lang=pt_br&appid={$api_key}";
    
    // Restante do código mantido igual...
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < 300)) {
        return json_decode(file_get_contents($cache_file), true);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Só cria cache se a resposta for válida
    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        if(isset($data['cod']) && $data['cod'] === 200) {
            file_put_contents($cache_file, $response);
            return $data;
        }
    }
    return null;
}

// Busca clima para todas as cidades
$weather_data = [];
foreach ($cidades_meteorologicas as $city) {
    $weather_data[$city] = getWeatherData($city, $weather_api_key);
}

$news_api_key = '221e7b47fd874e8d812a54ffd933f030';
$news_query = urlencode('"Paraná" OR "PR"');

$news_url = "https://newsapi.org/v2/everything?q={$news_query}&language=pt&sortBy=publishedAt&apiKey={$news_api_key}";

// Usando cURL como alternativa mais confiável
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $news_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'RadioTFM/1.0');
$news_data = curl_exec($ch);
curl_close($ch);

$news_items = [];
if ($news_data) {
    $response = json_decode($news_data, true);
    if ($response['status'] === 'ok') {
        $news_items = array_slice($response['articles'], 0, 6);
    }
}
if ($response['status'] === 'ok') {
    $filtered_news = [];
    $keywords = ['paraná', 'paranaense', 'curitiba', 'ponta grossa', 'maringá', 'londrina',
                 'cascavel', 'foz do iguaçu', 'guarapuava', 'toledo', 'paranaguá',
                 'umuarama', 'campo mourão', 'apucarana'];

    foreach ($response['articles'] as $article) {
        // Concatenar campos com verificação de existência
        $text = mb_strtolower(
            ($article['title'] ?? '') . ' ' .
            ($article['description'] ?? '') . ' ' .
            ($article['content'] ?? '') . ' ' .
            ($article['source']['name'] ?? '')
        );

        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $filtered_news[] = $article;
                break;
            }
        }
    }

    // Limita para os 6 mais recentes
    $news_items = array_slice($filtered_news, 0, 6);
}

// Mantenha o resto do seu código PHP existente
session_start();
require_once 'conexaoLocal.php';
?>

<html lang="pt-br">
<head>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Radio T FM</title>
    <style>
        :root {
            --primary-color: #652C67;
            --primary-light: #7c43bd;
            --primary-dark: #212121;
            --accent-color: #ff4081;
            --text-light: #f5f5f5;
            --text-dark: #212121;
            --background-light: #f9f9f9;
            --grey-light: #e0e0e0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        html, body {
            overflow: auto;
            -webkit-overflow-scrolling: touch; /* Scroll suave no iOS */
            height: 100%; /* Adicionado */
        }

        body {
            background-color: var(--background-light);
            color: var(--text-dark);
            line-height: 1.6;
            touch-action: pan-y;
            overflow-x: hidden;
            min-height: 100vh;
        }

        header {
            background-color: var(--primary-color);
            color: var(--text-light);
            padding: 0.2rem 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .container {
            position: relative;
            padding: 0 100px;
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            margin-right: 1rem;
        }

        .logo img {
            height: 100px;
            width: auto;
            transition: transform 0.3s ease;
            margin-right: 15px;
        }

        .logo img:hover {
            transform: scale(1.15);
        }

        .logo i {
            margin-right: 10px;
            font-size: 2rem;
        }

        nav ul {
            display: flex;
            list-style: none;
        }

        nav ul li {
            margin-left: 1.2rem;
            position: relative;
        }

        nav ul li:not(:last-child)::after {
            content: '';
            position: absolute;
            right: -0.6rem;
            top: 50%;
            transform: translateY(-50%);
            height: 0;
            width: 2px;
            background-color: #FFFFFF;
            transition: height 0.3s ease, box-shadow 0.3s ease;
            opacity: 0;
        }

        nav ul li:hover:not(:last-child)::after {
            height: 70%;
            opacity: 1;
            box-shadow: 0 0 8px rgba(255, 255, 255, 0.7);
        }

        nav ul li a {
            color: var(--text-light);
            text-decoration: none;
            transition: color 0.3s, font-size 0.3s;
            font-weight: 500;
            font-size: 1.1rem;
            padding: 0.5rem 0;
            display: block;
        }

        nav ul li a i {
            margin-right: 8px;
            font-size: 0.9em;
        }

        nav ul li a:hover {
            color: #FFFFFF;
            font-size: 1.2rem;
            text-shadow: 0 0 8px rgba(255, 255, 255, 0.5);
        }

        .mobile-menu {
            position: fixed;
            top: 0;
            left: -100%;
            width: 280px;
            height: 100vh;
            background: var(--primary-color);
            z-index: 1000;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 30px 20px;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.2);
        }

        .mobile-menu.active {
            left: 0;
        }

        .mobile-menu ul {
            flex-direction: column;
            margin-top: 40px;
            padding-left: 15px;
        }

        .mobile-menu li {
            margin: 20px 0;
            position: relative;
            transition: transform 0.3s ease;
        }

        .mobile-menu li a {
            color: var(--text-light);
            font-size: 1.1rem;
            font-weight: 500;
            text-decoration: none;
            display: block;
            padding: 8px 0;
            position: relative;
            transition: all 0.3s ease;
        }

        .mobile-menu li a:hover:before {
            content: '\f054';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            left: -20px;
            color: var(--accent-color);
            transition: all 0.3s ease;
        }

        .menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(3px);
            z-index: 999;
        }

        .mobile-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .mobile-menu-close {
            color: var(--text-light);
            font-size: 1.8rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .mobile-menu-close:hover {
            color: var(--accent-color);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.5rem;
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 1001;
            transition: transform 0.3s ease;
        }

        .mobile-menu-btn:hover {
            transform: translateY(-50%) scale(1.1);
        }

        .hero {
            background: linear-gradient(to right, var(--primary-dark), var(--primary-color));
            color: var(--text-light);
            padding: 3rem 0;
            text-align: center;
        }

        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .player-container {
            background-color: #652C67;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: -50px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            max-width: 700px;
            margin-left: 0;
            margin-right: auto;
        }

        .player-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .radio-selector {
            flex: 1;
            padding: 0.5rem;
            background-color: rgba(0, 0, 0, 0.1);
            border: none;
            border-radius: 4px;
            color: var(--text-light);
            outline: none;
            margin-right: 1rem;
        }

        .radio-selector option {
            background-color: var(--primary-dark);
        }

        .now-playing {
            color: white;
            font-size: 1.2rem;
            font-weight: 500;
            text-align: left; 
            width: 100%;
        }

        .connecting-status {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 1rem;
            display: none;
            align-items: center;
            gap: 12px;
            z-index: 1000;
            backdrop-filter: blur(2px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: none;
        }

        .connecting-status.active {
            display: flex !important;
            animation: pulse 1.5s infinite;
        }

        @@keyframes pulse {
            0% { transform: translate(-50%, -50%) scale(0.95); }
            50% { transform: translate(-50%, -50%) scale(1); }
            100% { transform: translate(-50%, -50%) scale(0.95); }
        }

        .player-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin: 1rem 0;
            position: relative;
        }

        .cover-art {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            background-color: rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .cover-art.loading {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        #radio-cover {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .now-playing-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center; 
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.2);
            padding: 8px 12px;
            border-radius: 6px;
            max-width: calc(100% - 200px);
            text-align: center; 
        }

        .now-playing-text {
            font-weight: bold;
            color: white;
            font-size: 1rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .radio-city {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
            margin-top: 2px;
        }

        .play-btn {
            width: 120px;
            height: 75px;
            border-radius: 10%;
            background-color: #FFFFFF;
            color: #000000;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            margin: 0 1rem;
            transition: all 0.3s ease;
        }

        .play-btn:hover {
            transform: scale(1.1);
        }

        .play-btn i {
            transition: transform 0.3s ease;
        }

        .play-btn:hover i {
            transform: scale(1.1);
        }

        .play-btn.loading {
            position: relative;
            cursor: not-allowed;
        }

        .play-btn.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        .volume-container {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            color: var(--text-light);
        }

        .volume-slider {
            width: 100px;
            margin: 0 10px;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            color: var(--primary-color);
            position: relative;
        }

        .section-title::after {
            content: '';
            display: block;
            width: 60px;
            height: 3px;
            background-color: var(--accent-color);
            margin: 0.8rem auto 0;
        }

        .map-section {
            padding: 2rem 0;
        }

        .map-container {
            background-color: transparent;
            border-radius: 8px;
            overflow: hidden; 
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            min-height: 400px;
        }

        .map-container img {
            width: 100%;
            height: auto;
            display: block;
            object-fit: contain;
        }

        .map-overlay {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            overflow-y: auto;
            border-left: 4px solid var(--primary-color);
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            width: 400px;
        }

        .radio-list {
            list-style: none;
            width: 100%;
        }

        .radio-list li {
            padding: 0.8rem 0.5rem;
            border-bottom: 1px solid var(--grey-light);
            cursor: pointer;
            transition: background-color 0.2s, color 0.2s;
            display: flex;
            align-items: center;
            white-space: nowrap;
            overflow: visible;
            text-overflow: clip;
        }

        .radio-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            width: 100%;
            padding: 0.8rem 0.5rem;
        }

        .radio-list li:hover .radio-link {
            color: white;
        }

        .radio-list li:hover .radio-link .radio-phone,
        .radio-list li:hover .radio-link .radio-address,
        .radio-list li:hover .radio-link .radio-cep {
            color: white !important;
        }

        .radio-info {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .radio-name {
            font-weight: 500;
        }

        .radio-phone {
            font-size: 0.85rem;
            color: var(--primary-light);
            margin-top: 2px;
        }
        
        .radio-address {
            font-size: 0.85rem;
            color: #666;
            margin-top: 2px;
            display: flex;
            align-items: center;
            flex-direction: row;
        }

        .radio-address:before {
            content: '\f3c5';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            margin-right: 5px;
            font-size: 0.8rem;
            color: var(--accent-color);
        }
        
        .radio-cep {
            font-size: 0.85rem;
            color: #666;
            margin-top: 2px;
            margin-left: 18px;
        }

        .radio-list li:before {
            content: '\f3cd';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            margin-right: 10px;
            color: var(--primary-color);
        }

        .radio-list li:hover {
            background-color: var(--primary-color);
            color: white;
            border-radius: 4px;
        }

        .radio-list li:hover:before {
            color: white;
        }
        
        .radio-list li:hover .radio-name,
        .radio-list li:hover .radio-phone,
        .radio-list li:hover .radio-address,
        .radio-list li:hover .radio-phone:before,
        .radio-list li:hover .radio-address:before {
            color: white;
        }

        .radio-list li:last-child {
            border-bottom: none;
        }

        .loading-indicator {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #666;
            padding: 3rem 0;
        }

        .error-message {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #d32f2f;
            padding: 3rem 0;
        }

        .error-message .fa-exclamation-circle {
            margin-bottom: 1rem;
        }

        .retry-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 4px;
            margin-top: 1rem;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .retry-btn:hover {
            background-color: var(--accent-color);
        }
        
        .no-results {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 0;
            color: #666;
        }

        .no-results i {
            margin-bottom: 1rem;
            font-size: 2rem;
            color: #ccc;
        }

        .program-schedule {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid var(--grey-light);
            margin-bottom: 1.5rem;
            overflow-x: auto;
        }

        .tab-btn {
            padding: 0.8rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            color: #666;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .tab-btn.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
        .schedule-grid {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 1rem;
        }

        .time-slot {
            font-weight: bold;
            color: var(--primary-color);
        }

        .program-item {
            background-color: var(--background-light);
            padding: 0.8rem;
            border-radius: 4px;
            margin-bottom: 0.8rem;
        }

        .program-name {
            font-weight: bold;
            margin-bottom: 0.3rem;
        }

        .program-host {
            font-size: 0.9rem;
            color: #666;
        }

        .program-host a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }

        .program-host a:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
        .about-section {
            padding: 3rem 0;
            background-color: var(--primary-dark);
            color: var(--text-light);
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .about-card {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 1.5rem;
        }

        .about-card h3 {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .about-card h3 i {
            margin-right: 0.5rem;
            color: var(--accent-color);
        }

        .team-member {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .team-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #ddd;
            margin-right: 1rem;
            background-size: cover;
            background-position: center;
        }

        .team-info h4 {
            margin-bottom: 0.2rem;
        }

        .team-role {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        footer {
            background-color: var(--primary-dark);
            color: var(--text-light);
            padding: 3rem 0 1rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-heading {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .footer-heading::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 40px;
            height: 3px;
            background-color: var(--accent-color);
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.8rem;
        }

        .footer-links a {
            color: var(--text-light);
            opacity: 0.8;
            text-decoration: none;
            transition: opacity 0.3s;
        }

        .footer-links a:hover {
            opacity: 1;
        }

        .contact-info {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .contact-info i {
            margin-right: 0.8rem;
            color: var(--accent-color);
        }

        .copyright {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
            opacity: 0.7;
        }

        .app-download {
            margin-top: 1.5rem;
            display: flex;
            justify-content: center;
        }

        .google-play-badge {
            height: 50px;
            transition: transform 0.3s ease;
        }

        .google-play-badge:hover {
            transform: scale(1.05);
        }

        .weather-section {
            padding: 2rem 0;
            background: #f8f9fa;
            position: relative;
        }

        .weather-grid {
            display: flex;
            overflow-x: auto;
            scroll-behavior: smooth;
            gap: 1rem;
            padding: 1rem 0;
            -webkit-overflow-scrolling: touch; /* Scroll suave no iOS */
            scrollbar-width: none; /* Esconder scrollbar no Firefox */
            margin: 0 -20px;
            padding: 0 20px;
        }

        .weather-grid::-webkit-scrollbar {
            display: none;
        }

        .weather-card {
            min-width: 220px;
            flex-shrink: 0;
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            scroll-snap-align: start;
        }

        .weather-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .weather-card.error {
            background: #fff3f3;
            border: 1px solid #ffd6d6;
        }

        .weather-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.9);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            display: none; /* Ocultar por padrão em mobile */
        }

        .weather-nav.prev {
            left: 10px;
        }

        .weather-nav.next {
            right: 10px;
        }

        .weather-card h3 {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding: 0 10px;
            color: #d32f2f;
        }

        .weather-card.error .weather-info {
            color: #666;
            font-style: italic;
        }

        .weather-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .news-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .news-card:hover {
            transform: translateY(-5px);
        }

        .news-image {
            height: 200px;
            overflow: hidden;
        }

        .news-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .news-card:hover .news-image img {
            transform: scale(1.05);
        }

        .news-content {
            padding: 1.5rem;
        }

        .news-content h3 {
            color: var(--primary-dark);
            margin-bottom: 0.8rem;
            font-size: 1.1rem;
        }

        .news-excerpt {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .news-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
        }

        .news-source {
            color: var(--primary-color);
            font-weight: 500;
        }

        .news-link {
            color: var(--accent-color);
            text-decoration: none;
            transition: color 0.3s;
        }

        .news-link:hover {
            color: var(--primary-color);
        }

        .no-news {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-news i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--grey-light);
        }

        .tnews-button-container {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 3rem;
            text-align: right;
            width: 300px;
        }

        .tnews-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(166, 76, 166, 0.4);
            margin-bottom: 1rem;
            gap: 10px;
        }

        .tnews-button i {
            margin-right: 10px;
            font-size: 1.3rem;
        }

        .tnews-button:hover {
            background-color: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 64, 129, 0.5);
        }

        .tnews-description {
            color: var(--text-dark);
            max-width: 600px;
            font-size: 0.9rem;
            margin-top: 10px;
            line-height: 1.4;
        }

        @media (min-width: 768px) {
            .map-container {
                flex-direction: row;
            }
    
            .map-container img {
                width: calc(100% - 400px);
                height: auto;
                max-height: none;
            }
    
            .map-overlay {
                position: absolute;
                top: 0;
                right: 0;
                bottom: 0;
                width: 400px;
                height: 100%;
            }

            .weather-nav {
                display: block;
            }
            
            .weather-grid {
                margin: 0 -30px;
                padding: 0 30px;
            }
        }

        @media (min-width: 769px) {
            .mobile-menu,
            .menu-overlay {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            nav ul {
                display: none;
            }

            nav ul li a i {
                display: none;
            }
            
            .mobile-menu li a i {
                display: inline-block !important;
                width: 20px;
            }

            .weather-info {
                flex-wrap: wrap;
            }

            .news-grid {
                grid-template-columns: 1fr;
            }
            
            .news-image {
                height: 150px;
            }

            .container {
                padding: 0 20px;
                width: 100%;
            }

            .header-content {
                justify-content: flex-start;
                padding-left: 15px;
            }

            .logo {
                margin-right: auto;
            }

            .tnews-button-container {
                position: static !important;
                width: 100% !important;
                margin-top: 2rem !important;
                text-align: center !important;
                transform: none !important;
                right: auto !important;
                top: auto !important;
            }
            
            .container > .player-container {
                margin-bottom: 1rem;
            }

            .logo img {
                height: 60px;
            }

            .tnews-button {
                padding: 0.8rem 1.5rem;
                width: 100%;
                max-width: 300px;
                margin: 0 auto;
                font-size: 1.1rem;
            }

            .tnews-description {
                margin: 1rem auto !important;
                padding: 0 15px;
            }

            .mobile-menu-btn {
                display: block;
                right: 15px;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .player-container {
                margin-top: -20px;
                padding: 1rem;
                position: relative;
                z-index: 1;
            }

            .radio-selector {
                font-size: 0.9rem;
            }

            .play-btn {
                width: 80px;
                height: 50px;
                font-size: 1.2rem;
            }

            .now-playing-info {
                margin-bottom: 1rem;
                width: 100%;
                max-width: 100%;
            }

            .player-controls {
                flex-wrap: wrap;
                justify-content: center;
            }

            .tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .tab-btn {
                padding: 0.8rem 1rem;
                font-size: 0.9rem;
            }

            .map-container {
                height: auto;
                flex-direction: column;
            }
            .map-container img {
                width: 100%;
                height: auto;
            }
            .map-overlay {
                position: relative;
                width: 100%;
                height: auto;
                border-left: none;
                border-top: 4px solid var(--primary-color);
            }

            .schedule-grid {
                grid-template-columns: 1fr;
            }

            .time-slot {
                background-color: var(--primary-light);
                color: white;
                padding: 0.3rem 0.8rem;
                border-radius: 4px;
                display: inline-block;
                margin-bottom: 0.5rem;
            }

            .about-card {
                padding: 1rem;
            }

            .team-photo {
                width: 40px;
                height: 40px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .google-play-badge {
                height: 40px;
            }

            h1 {
                font-size: 1.8rem !important;
            }
    
            h2 {
                font-size: 1.5rem !important;
            }
    
            p, li {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .radio-selector {
                font-size: 0.8rem;
                margin-right: 0.5rem;
            }

            .weather-grid {
                grid-template-columns: 1fr 1fr;
            }

            .now-playing-text {
                font-size: 0.9rem;
            }

            .radio-city {
                font-size: 0.8rem;
            }

            .tnews-button {
                font-size: 1rem !important;
                padding: 0.6rem 1.2rem !important;
            }

            .map-overlay {
                padding: 1rem;
            }

            .radio-list li {
                padding: 0.6rem 0;
            }
        }

        .select2-container--default .select2-selection--single {
            background-color: rgba(0, 0, 0, 0.1) !important;
            border: none !important;
            border-radius: 4px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
            color: white !important;
        }

        .select2-dropdown {
            background-color: var(--primary-dark) !important;
            border: none !important;
        }
        
        .weather-info {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            font-size: 0.9rem;
        }

        .weather-temp {
            font-weight: bold;
            color: var(--primary-color);
        }

        .weather-icon {
            width: 24px;
            height: 24px;
        }

        .weather-description {
            color: #666;
            font-style: italic;
        }

    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Controle de abas
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    tabBtns.forEach(b => b.classList.remove('active'));
                    
                    btn.classList.add('active');
                    
                    tabContents.forEach(content => {
                        content.style.display = 'none';
                    });
                    
                    const tabId = btn.getAttribute('data-tab');
                    document.getElementById(tabId).style.display = 'block';
                });
            });
            
            if (tabBtns.length > 0) {
                tabBtns[0].click();
            }
        });
    </script>
    </head>
        <body>
            <header>
                <div class="container header-content">
                    <div class="logo">
                        <img src="assets\img\logot.png" alt="Radio T Logo" height="40">
                    </div>
                    <nav>
                        <ul>
                            <li><a href="#home">Ao Vivo</a></li>
                            <li><a href="#radios">Rádios</a></li>
                            <li><a href="#schedule">Programação</a></li>
                            <li><a href="#clima">Clima</a></li>
                            <li><a href="#noticias">Notícias</a></li>
                            <li><a href="#about">Institucional</a></li>
                        </ul>
                        <button class="mobile-menu-btn">
                            <i class="fas fa-bars"></i>
                        </button>
                    </nav>
                </div>
            </header>

            <div class="mobile-menu">
                <div class="mobile-menu-header">
                    <img src="assets\img\logot.png" alt="Logo" style="height: 40px;">
                    <i class="fas fa-times mobile-menu-close"></i>
                </div>
                <ul>
                    <li><a href="#home"><i class="fas fa-home"></i> Ao Vivo</a></li>
                    <li><a href="#radios"><i class="fas fa-radio"></i> Rádios</a></li>
                    <li><a href="#schedule"><i class="fas fa-tv"></i> Programação</a></li>
                    <li><a href="#clima"><i class="fas fa-cloud-sun"></i> Clima</a></li>
                    <li><a href="#noticias"><i class="fas fa-newspaper"></i> Notícias</a></li>
                    <li><a href="#about"><i class="fas fa-building"></i> Institucional</a></li>
                </ul>
            </div>
            <div class="menu-overlay"></div>
                <section id="home" class="hero">
                    <div class="container">
                        <h1>Sua rede de rádios pelo Paraná</h1>
                        <p>Acompanhe as principais notícias e atrações da sua região favorita, onde quer que você esteja.</p>
                    </div>
                </section>

                <script>
                    // Controle do Menu Mobile
                    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
                    const mobileMenu = document.querySelector('.mobile-menu');
                    const menuOverlay = document.querySelector('.menu-overlay');
                    const mobileMenuClose = document.querySelector('.mobile-menu-close');

                    function toggleMenu() {
                        mobileMenu.classList.toggle('active');
                        menuOverlay.style.display = mobileMenu.classList.contains('active') ? 'block' : 'none';
                    }

                    mobileMenuBtn.addEventListener('click', toggleMenu);
                    mobileMenuClose.addEventListener('click', toggleMenu);
                    menuOverlay.addEventListener('click', toggleMenu);

                    mobileMenuBtn.addEventListener('click', () => {
                        mobileMenu.classList.add('active');
                        menuOverlay.style.display = 'block';
                    });

                    menuOverlay.addEventListener('click', () => {
                        mobileMenu.classList.remove('active');
                        menuOverlay.style.display = 'none';
                    });

                    // Fechar menu ao clicar em um link
                    document.querySelectorAll('.mobile-menu a').forEach(link => {
                        link.addEventListener('click', toggleMenu);
                        });
                </script>

                <div class="container">
                    <div class="player-container">
                        <div class="player-header">
                            <select class="radio-selector">
                                <?php if (!empty($radios_player)): ?>
                                <?php foreach ($radios_player as $radio): 
                                    $urlplay = $radio['urlplay'] ?? '';
                                    $isValid = filter_var($urlplay, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//i', $urlplay);
                                    if (!$isValid) continue;?>
                            <option 
                                value="<?= $radio['id'] ?>" 
                                data-stream="<?= htmlspecialchars($urlplay) ?>"
                                data-prog="<?= htmlspecialchars($radio['prog']) ?>"
                                data-cidade="<?= htmlspecialchars($radio['cidade']) ?>"
                                data-metada="<?= htmlspecialchars($radio['metada_url']) ?>">
                                T <?= ($radio['id'] == 1) ? $radio['nome_fant'] : $radio['nome'] ?> FM
                            </option>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">Nenhuma rádio disponível</option>
                            <?php endif; ?>
                            </select>
                        </div>
                        <div class="connecting-status" id="connecting-status">
                            <i class="fas fa-circle-notch fa-spin"></i>
                                Conectando...
                        </div>
                        <audio id="html5-player" hidden></audio>
                        <div class="player-controls">
                        <div class="cover-art">
                            <img id="radio-cover" src="assets/img/default-cover.png" alt="Capa do Programa">
                        </div>
                            <div class="now-playing-info">
                                <div class="now-playing" id="now-playing"><?= $radios_player[0]['prog'] ?? 'Programa não disponível' ?></div>
                            </div>
                            <button class="play-btn" id="play-pause-btn">
                                <i class="fas fa-play"></i>
                            </button>
                        </div>
                        <div class="volume-container">
                            <i class="fas fa-volume-down"></i>
                            <input type="range" min="0" max="100" value="80" class="volume-slider">
                            <i class="fas fa-volume-up"></i>
                        </div>
                    </div>
                    <div class="tnews-button-container" style="text-align: center; margin-top: 2rem;">
                        <a href="https://www.tnewsnoar.com.br/" target="_blank" class="tnews-button" style="display: inline-block; background-color: var(--primary-color); color: white; padding: 0.8rem 1.5rem; border-radius: 4px; text-decoration: none; font-weight: bold; transition: background-color 0.3s, transform 0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                            <i class="fas fa-newspaper"></i> T News
                        </a>
                        <p class="tnews-description" style="margin-top: 1rem; color: #666; max-width: 600px; margin-left: auto; margin-right: auto;">Acompanhe o TNews com Marcelo Almeida e Roberta Canetti, de segunda a sexta-feira, ao vivo, das 7h às 8h.</p>
                        <a href="https://wa.me/5541984010001" target="_blank" 
                            class="tnews-button" 
                            style="display: inline-block; background-color: #25D366; color: white; padding: 0.8rem 1.5rem; border-radius: 4px; text-decoration: none; font-weight: bold; transition: background-color 0.3s, transform 0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.2); margin-top: 1rem;">
                            <i class="fab fa-whatsapp"></i> Envie sua mensagem
                        </a>
                    </div>
                </div>

                <section id="radios" class="map-section">
                    <div class="container">
                        <h2 class="section-title">Nossas Rádios</h2>
                        <div class="map-container">
                            <img src="assets\img\mapat.png" alt="Mapa do Paraná com localização das rádios">
                            <div class="map-overlay">
                                <h3>Rádios T</h3>
                                <?php if (!empty($error_message)): ?>
                                    <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
                                <?php elseif (empty($radios_map)): ?>
                                    <div class="no-radios">Nenhuma rádio encontrada.</div>
                                <?php else: ?>
                                    <ul class="radio-list">
                                        <?php foreach ($radios_map as $radio): ?>
                                            <li>
                                                <a href="https://www.google.com/maps/search/?api=1&query=<?= 
                                                    urlencode(
                                                        ($radio['endereco'] ?? '') . ', ' . 
                                                        ($radio['cidade'] ?? '') . ', ' . 
                                                        ($radio['uf'] ?? '') . ', ' . 
                                                        ($radio['cep'] ?? '')
                                                    ) ?>" 
                                                target="_blank"
                                                class="radio-link">
                                                    <div class="radio-info">
                                                        <div class="radio-name">T <?= htmlspecialchars($radio['nome']) ?> FM - <?= htmlspecialchars($radio['freq']) ?> MHz</div>
                                                        <?php if (!empty($radio['tel'])): ?>
                                                            <div class="radio-phone"><?= htmlspecialchars($radio['tel']) ?></div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($radio['endereco'])): ?>
                                                            <div class="radio-address"><?= htmlspecialchars($radio['endereco']) ?></div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($radio['cidade']) && !empty($radio['uf'])): ?>
                                                            <div class="radio-cep"><?= htmlspecialchars($radio['cidade']) ?>, <?= htmlspecialchars($radio['uf']) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="schedule" class="content-section">
                    <div class="container">
                        <h2 class="section-title">Programação</h2>
                        
                        <div class="program-schedule">
                            <div class="tabs">
                                <?php 
                                $dias_semana = [
                                    1 => 'Segunda',
                                    2 => 'Terça',
                                    3 => 'Quarta',
                                    4 => 'Quinta',
                                    5 => 'Sexta',
                                    6 => 'Sábado',
                                    0 => 'Domingo'
                                ];
                                
                                foreach ($dias_semana as $num => $dia): ?>
                                    <button class="tab-btn <?= $num == 1 ? 'active' : '' ?>" 
                                            data-tab="tab-<?= strtolower($dia) ?>">
                                        <?= $dia ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php foreach ($dias_semana as $num => $dia): ?>
                                <div id="tab-<?= strtolower($dia) ?>" 
                                    class="tab-content <?= $num == 1 ? 'active' : '' ?>">
                                    <div class="schedule-grid">
                                        <?php if (!empty($programas_por_dia[$num])): ?>
                                            <?php foreach ($programas_por_dia[$num] as $programa): ?>
                                                <div class="time-slot">
                                                    <?= date("H:i", strtotime($programa['inicio'])) ?>
                                                </div>
                                                <div class="program-item">
                                                    <div class="program-name"><?= htmlspecialchars($programa['nome']) ?></div>
                                                    <div class="program-host"><?= strip_tags($programa['inf'], '<a>') ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="no-results">
                                                <i class="fas fa-calendar-times"></i>
                                                Nenhum programa cadastrado neste dia
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <section id="clima" class="weather-section">
                <div class="container">
                    <h2 class="section-title">Clima nas Cidades</h2>
                    <button class="weather-nav prev"><i class="fas fa-chevron-left"></i></button>
                    <button class="weather-nav next"><i class="fas fa-chevron-right"></i></button>
                    <div class="weather-grid">
                            <?php if (!empty($weather_data)): ?>
                                <?php foreach ($weather_data as $city => $data): ?>
                                    <?php if (!empty($data) && isset($data['weather'])): ?>
                                        <div class="weather-card">
                                            <h3><?= htmlspecialchars(str_replace('_', ' ', $city)) ?></h3>
                                            <div class="weather-info">
                                                <img src="http://openweathermap.org/img/wn/<?= $data['weather'][0]['icon'] ?>.png" 
                                                    class="weather-icon" 
                                                    alt="<?= $data['weather'][0]['description'] ?>">
                                                <span class="weather-temp"><?= round($data['main']['temp']) ?>°C</span>
                                                <span class="weather-description">
                                                    <?= ucfirst($data['weather'][0]['description']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="weather-card error">
                                            <h3><?= htmlspecialchars(str_replace('_', ' ', $city)) ?></h3>
                                            <div class="weather-info">
                                                <i class="fas fa-cloud-sun-rain"></i>
                                                Dados indisponíveis
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="error-message">
                                    <i class="fas fa-cloud-sun-rain"></i>
                                    Clima indisponível no momento
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section id="noticias" class="content-section">
                    <div class="container">
                        <h2 class="section-title">Principais Notícias</h2>
                        <?php if(empty($news_items)): ?>
                            <div class="no-news">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p>Não foi possível carregar as notícias neste momento</p>
                            </div>
                        <?php else: ?>
                            <div class="news-grid">
                                <?php foreach(array_slice($news_items, 0, 6) as $item): ?>
                                    <article class="news-card">
                                        <?php if(!empty($item['urlToImage'])): ?>
                                            <div class="news-image">
                                                <img src="<?= htmlspecialchars($item['urlToImage']) ?>" 
                                                    alt="<?= htmlspecialchars($item['title']) ?>"
                                                    loading="lazy">
                                            </div>
                                        <?php endif; ?>
                                        <div class="news-content">
                                            <h3><?= htmlspecialchars($item['title']) ?></h3>
                                            <p class="news-excerpt">
                                                <?= htmlspecialchars(substr($item['description'], 0, 150)) ?>...
                                            </p>
                                            <div class="news-meta">
                                                <span class="news-source"><?= htmlspecialchars($item['source']['name']) ?></span>
                                                <a href="<?= htmlspecialchars($item['url']) ?>" 
                                                target="_blank" 
                                                class="news-link">
                                                    Leia mais <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section id="about" class="about-section">
                    <div class="container">
                        <h2 class="section-title" style="color: var(--text-light);">Institucional</h2>
                        
                        <div class="about-grid">
                            <!-- História -->
                            <div class="about-card">
                                <h3><i class="fas fa-history"></i> Nossa História</h3>
                                <p>A Rádio T FM iniciou suas atividades em 1991, em Ponta Grossa, com o nome de Rádio Tropical FM, dedicação e trabalho árduo marcaram o início da emissora, que futuramente teria seu nome conhecido pelas conceituadas empresas que integram o estado do Paraná, agradando com a sua promoção a todos os ouvintes e conquistando elevados índices de audiência. 
                                    Hoje somos a maior Rede de Rádio do Paraná, transmitindo para todo o estado uma programação única e simultânea, gera e enviada para todas as emissoras através de interligação online, com sistema inovador de transmissão em rede de fibra ótica, sem similar no país. </p>
                                <p>Suas raízes se fortaleceram ainda mais, transformando-se em uma das maiores redes de rádio do Sul do Brasil, com mais de 20 emissoras distribuídas em todo o Paraná.</p>
                            </div>
                            
                            <!-- Equipe -->
                            <div class="about-card">
                                <h3><i class="fas fa-users"></i> Nossa Equipe</h3>
                                
                                <?php if (!empty($equipe)): ?>
                                    <?php foreach ($equipe as $membro): ?>
                                        <div class="team-member">
                                            <div class="team-photo"></div>
                                            <div class="team-info">
                                                <h4><?= htmlspecialchars($membro['nome']) ?></h4>
                                                <div class="team-role"><?= htmlspecialchars($membro['cargo']) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p style="color: #666; text-align: center;">Nossa equipe está carregando...</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Contato -->
                            <div class="about-card">
                                <h3><i class="fas fa-envelope"></i> Contato</h3>
                                
                                <div class="contact-info">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div>
                                        <p>Matriz</p>
                                        <p>Av. General Carlos Cavalcanti, 1386 - Uvaranas - Ponta Grossa, PR</p>
                                        <p>CEP 84025-000</p>
                                    </div>
                                </div>
                                
                                <div class="contact-info">
                                    <i class="fas fa-phone"></i>
                                    <div>
                                        <p>Central de Atendimento</p>
                                        <p>(42) 3220-9000</p>
                                        <p>Whatsapp do ouvinte: (41) 98401-0001</p>
                                    </div>
                                </div>
                                
                                <div class="contact-info">
                                    <i class="fas fa-envelope"></i>
                                    <div>
                                        <p>E-mail</p>
                                        <p>falecom@radiot.fm</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <footer>
                    <div class="container">
                        <div class="footer-grid">
                            <div>
                                <h3 class="footer-heading">Rádio T</h3>
                                <p>A maior rede de rádios do estado, conectando os paranaenses através de conteúdo de qualidade.</p>
                                <div style="margin-top: 1rem;">
                                    <a href="https://www.facebook.com/radiotfm/" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: none;">
                                        <i class="fab fa-facebook fa-lg" style="margin-right: 1rem; cursor: pointer;"></i>
                                    </a>
                                    <a href="https://www.instagram.com/radiotfm" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: none;">
                                        <i class="fab fa-instagram fa-lg" style="margin-right: 1rem; cursor: pointer;"></i>
                                    </a>
                                    <a href="https://x.com/radio_t" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" 
                                            style="width: 1.3em; height: 1.3em; margin-right: 1rem; cursor: pointer; vertical-align: text-top;">
                                            <path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"
                                            style="fill: currentColor"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="footer-heading">Links Rápidos</h3>
                                <ul class="footer-links">
                                    <li><a href="#home">Home</a></li>
                                    <li><a href="#radios">Nossas Rádios</a></li>
                                    <li><a href="#schedule">Programação</a></li>
                                    <li><a href="#about">Institucional</a></li>
                                </ul>
                            </div>
                            <div>
                                <h3 class="footer-heading">Aplicativo</h3>
                                <p>Baixe nosso aplicativo e tenha acesso a todas as rádios em seu smartphone.</p>
                                <div style="margin-top: 1rem;">
                                    <a href="https://play.google.com/store/apps/details?id=app.radiosplay.radiost" target="_blank">
                                        <img src="assets\img\GooglePlay.png" alt="Google Play" class="google-play-badge" style="cursor: pointer;">
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="copyright">
                            &copy; Todos os Direitos reservados @ Rede T de Rádios.
                        </div>
                    </div>
                </footer>

                <script>
                    document.addEventListener('touchmove', function(e) {
                        if (e.touches.length > 1) {
                            e.preventDefault();
                        }
                    }, { passive: false });

                    document.querySelectorAll('button, a, [role="button"]').forEach(element => {
                        element.style.touchAction = 'manipulation';
                    });

                    document.addEventListener('DOMContentLoaded', function() {
                        const player = document.getElementById('html5-player');
                        const playPauseBtn = document.getElementById('play-pause-btn');
                        const radioSelector = document.querySelector('.radio-selector');
                        const nowPlaying = document.getElementById('now-playing');
                        const radioCity = document.getElementById('radio-city');
                        const connectingStatus = document.getElementById('connecting-status');
                        const volumeSlider = document.querySelector('.volume-slider');
                        const logo = document.querySelector('.logo img');
                        const weatherGrid = document.querySelector('.weather-grid');
                        const prevButton = document.querySelector('.weather-nav.prev');
                        const nextButton = document.querySelector('.weather-nav.next');

                        let clickCount = 0;
                        let timeout;
                        let currentStation = null;
                        let userInteracted = false;
                        let currentMetadaUrl = '';

                        if (weatherGrid) {
                            const cardWidth = document.querySelector('.weather-card').offsetWidth + 16; // Largura do card + gap
                            
                            nextButton.addEventListener('click', () => {
                                weatherGrid.scrollBy({
                                    left: cardWidth * 3, // Scroll de 3 cards
                                    behavior: 'smooth'
                                });
                            });

                            prevButton.addEventListener('click', () => {
                                weatherGrid.scrollBy({
                                    left: -cardWidth * 3,
                                    behavior: 'smooth'
                                });
                            });

                            // Habilitar/desabilitar botões baseado na posição do scroll
                            weatherGrid.addEventListener('scroll', () => {
                                prevButton.disabled = weatherGrid.scrollLeft === 0;
                                nextButton.disabled = weatherGrid.scrollLeft + weatherGrid.clientWidth >= weatherGrid.scrollWidth;
                            });
                        }

                        // Easter egg do logo
                        logo.addEventListener('click', function() {
                            clickCount++;
                            clearTimeout(timeout);
                            timeout = setTimeout(() => clickCount = 0, 2000);

                            if(clickCount === 15) {
                                window.open('https://www.youtube.com/watch?v=xvFZjo5PgG0', '_blank');
                                clickCount = 0;
                            }
                        });

                        // Função de inicialização do player
                        function initializePlayer() {
                            const ultimaRadioSalva = localStorage.getItem('ultimaRadio');
                            
                            if(ultimaRadioSalva) {
                                const optionToSelect = Array.from(radioSelector.options).find(option => option.value === ultimaRadioSalva);
                                if(optionToSelect) {
                                    loadRadio(optionToSelect);
                                    radioSelector.value = ultimaRadioSalva;
                                    return;
                                }
                            }

                            const firstRadio = document.querySelector('.radio-selector option:first-child');
                            if(firstRadio) {
                                loadRadio(firstRadio);
                            }
                        }

                        // Função para carregar uma rádio
                        function loadRadio(option) {
                            currentStation = option.dataset.stream;
                            currentMetadaUrl = option.dataset.metada;
                            player.src = currentStation;
                            
                            // Resetar para dados estáticos enquanto carrega
                            document.getElementById('now-playing').textContent = option.dataset.prog;
                            document.getElementById('radio-cover').src = 'assets/img/default-cover.png';
                            
                            // Forçar busca imediata de metadados
                            if (currentMetadaUrl) {
                                buscaApi();
                            }
                            
                            player.load();
                        }

                        function buscaApi() {
                            if (!currentMetadaUrl) return;

                            fetch(currentMetadaUrl)
                                .then((response) => response.json())
                                .then((data) => {
                                    // Atualiza título e imagem
                                    const nowPlaying = document.getElementById('now-playing');
                                    const coverImg = document.getElementById('radio-cover');
                                    
                                    nowPlaying.textContent = data.nowplaying || 'Programa não disponível';
                                    
                                    if (data.coverart) {
                                        // Forçar atualização da imagem (evitar cache)
                                        coverImg.src = data.coverart + '?t=' + new Date().getTime();
                                    } else {
                                        coverImg.src = 'assets/img/default-cover.png'; // Imagem padrão
                                    }
                                })
                                .catch(() => {
                                    console.error('Erro ao buscar metadados');
                                });
                        }
                        setInterval(buscaApi, 15000);


                        // Controle de volume exponencial
                        function exponentialVolume(value) {
                            const minp = 0;
                            const maxp = 100;
                            const minv = Math.log(0.001);
                            const maxv = Math.log(1);
                            const scale = (maxv - minv) / (maxp - minp);
                            return Math.exp(minv + scale * (value - minp));
                        }

                        // Inicialização
                        initializePlayer();
                        player.volume = exponentialVolume(80);
                        volumeSlider.value = 80;

                        // Controle de volume
                        volumeSlider.addEventListener('input', (e) => {
                            player.volume = exponentialVolume(e.target.value);
                        });

                        // Controle de play/pause
                        playPauseBtn.addEventListener('click', async () => {
                            try {
                                if(!userInteracted) {
                                    // Primeira interação necessária para autoplay
                                    await player.play();
                                    userInteracted = true;
                                    return;
                                }

                                if(player.paused) {
                                    connectingStatus.classList.add('active');
                                    await player.play();
                                } else {
                                    player.pause();
                                }
                                updateButtonState();
                            } catch (error) {
                                console.error('Erro na reprodução:', error);
                                connectingStatus.classList.remove('active');
                                alert('Erro ao conectar: ' + error.message);
                            }
                        });

                        // Atualizar estado do botão
                        function updateButtonState() {
                            const icon = playPauseBtn.querySelector('i');
                            icon.classList.toggle('fa-play', player.paused);
                            icon.classList.toggle('fa-pause', !player.paused);
                        }

                        // Status de conexão
                        player.addEventListener('waiting', () => {
                            connectingStatus.classList.add('active');
                        });

                        player.addEventListener('playing', () => {
                            connectingStatus.classList.remove('active');
                        });

                        // Troca de rádio
                        radioSelector.addEventListener('change', async function() {
                            const selected = this.options[this.selectedIndex];
                            localStorage.setItem('ultimaRadio', this.value);
                            
                            try {
                                player.pause();
                                player.src = '';
                                await new Promise(resolve => player.onemptied = resolve);
                                
                                loadRadio(selected); // Já atualiza a URL de metadados
                                
                                if (userInteracted) {
                                    connectingStatus.classList.add('active');
                                    await new Promise(resolve => setTimeout(resolve, 100));
                                    await player.play();
                                }
                            } catch (error) {
                                console.error('Erro na troca de rádio:', error);
                                connectingStatus.classList.remove('active');
                            }
                        });

                        // Tratamento de erros
                        player.addEventListener('error', (e) => {
                            console.error('Erro no player:', e);
                            connectingStatus.classList.remove('active');
                            
                        });

                        // Atualizações de estado
                        player.addEventListener('play', updateButtonState);
                        player.addEventListener('pause', updateButtonState);
                    });
                </script>
    </body>
</html>