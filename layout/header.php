<?php
/**
 * Layout Header WMS CWE
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/alerts.php';
require_once __DIR__ . '/../helpers/format.php';

require_login();
$user = current_user();
$page_title = $page_title ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - <?= APP_NAME ?> <?= INSTITUTION_NAME ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>assets/img/logo-cwe.png">
    
    <!-- Google Fonts (Plus Jakarta Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons & FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.min.css">
    
    <!-- Custom Theme Style (Palm Emerald Theme) -->
    <style>
        :root {
            --primary-cwe: #0f5132;
            --primary-cwe-hover: #0a3622;
            --primary-cwe-light: #d1e7dd;
            --accent-gold: #d4a373;
            --sidebar-bg: #0b3d26;
            --sidebar-text: #e9ecef;
            --sidebar-active: #198754;
            --card-radius: 14px;
        }
        
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            max-width: 100vw;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f4f7f6;
            color: #2b2f33;
            height: 100vh;
        }
        
        /* Layout Structure */
        #wrapper {
            display: flex;
            height: 100vh;
            width: 100vw;
            max-width: 100vw;
            overflow: hidden;
            position: relative;
        }
        
        #sidebar {
            width: 270px;
            flex: 0 0 270px;
            height: 100vh;
            background: linear-gradient(180deg, #0b3d26 0%, #062316 100%);
            color: var(--sidebar-text);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1050;
            box-shadow: 4px 0 15px rgba(0,0,0,0.06);
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        #content-wrapper {
            flex: 1 1 0%;
            height: 100vh;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background-color: #f4f7f6;
            overflow-y: auto;
            overflow-x: hidden;
            position: relative;
        }

        #content-wrapper > main {
            flex: 1 0 auto;
        }

        /* Desktop View (>= 992px) */
        @media (min-width: 992px) {
            #sidebar.collapsed {
                margin-left: -270px;
            }
            #sidebarBackdrop {
                display: none !important;
            }
        }

        /* Mobile & Tablet View (< 992px) */
        @media (max-width: 991.98px) {
            #sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: 280px;
                max-width: 85vw;
                height: 100vh;
                transform: translateX(-100%);
                box-shadow: none;
                z-index: 1060;
            }
            
            #sidebar.show {
                transform: translateX(0);
                box-shadow: 10px 0 35px rgba(0, 0, 0, 0.45);
            }
            
            .topbar {
                padding: 0.6rem 1rem !important;
            }

            main.container-fluid {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
                padding-top: 1rem !important;
                padding-bottom: 1.5rem !important;
                overflow-x: hidden;
            }

            .row {
                --bs-gutter-x: 1rem;
            }
        }

        /* Sidebar Backdrop Overlay on Mobile */
        #sidebarBackdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(2px);
            z-index: 1055;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        #sidebarBackdrop.show {
            opacity: 1;
            visibility: visible;
        }
        
        /* Sidebar Styling */
        .sidebar-brand {
            padding: 1.5rem 1.25rem 1rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-nav {
            list-style: none;
            padding: 1rem 0.75rem;
            margin: 0;
        }
        .sidebar-heading {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.45);
            padding: 0.75rem 1rem 0.25rem 1rem;
            font-weight: 700;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.7rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 0.25rem;
            font-weight: 500;
            font-size: 0.92rem;
            transition: all 0.2s ease;
        }
        .sidebar-link i {
            font-size: 1.15rem;
            width: 28px;
            color: rgba(255, 255, 255, 0.9);
            transition: transform 0.2s, color 0.2s;
        }
        .sidebar-link:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.09);
        }
        .sidebar-link:hover i {
            transform: scale(1.12);
            color: #ffffff;
        }
        .sidebar-link.active {
            color: #ffffff;
            background: linear-gradient(90deg, #198754 0%, #157347 100%);
            box-shadow: 0 4px 10px rgba(25, 135, 84, 0.35);
            font-weight: 600;
        }
        .sidebar-link.active i {
            color: #ffffff;
        }
        
        /* Navbar */
        .topbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e9ecef;
            padding: 0.75rem 1.75rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: var(--card-radius);
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #f0f3f2;
            padding: 1.1rem 1.4rem;
            font-weight: 600;
            border-radius: var(--card-radius) var(--card-radius) 0 0 !important;
        }
        
        .btn-cwe-primary {
            background-color: #0f5132;
            color: #ffffff;
            border: none;
            font-weight: 600;
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-cwe-primary:hover {
            background-color: #0a3622;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(15, 81, 50, 0.25);
        }
        
        .badge-cwe {
            background-color: #d1e7dd;
            color: #0f5132;
            font-weight: 600;
        }
        
        /* Responsive Tables & DataTables */
        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }
        @media (max-width: 767.98px) {
            div.dataTables_wrapper div.dataTables_length,
            div.dataTables_wrapper div.dataTables_filter,
            div.dataTables_wrapper div.dataTables_info,
            div.dataTables_wrapper div.dataTables_paginate {
                text-align: center !important;
                margin-bottom: 0.5rem;
            }
        }

        /* Interactive Scan Floating Button on Mobile */
        .scan-pulse {
            box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7);
            animation: pulse 1.8s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(25, 135, 84, 0); }
            100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
        }
    </style>
</head>
<body>
<div id="wrapper">

