<?php

namespace App\Livewire\Mobile\Traits;

trait HasMobileTranslations
{
    public string $language = 'en';

    public function getTranslationsProperty(): array
    {
        $translations = [
            'en' => [
                // Navigation
                'navigation' => 'Navigation',
                'home' => 'Home',
                'estimates' => 'Estimates',
                'jobs' => 'Jobs',
                'profile' => 'Profile',
                'request_service' => 'Request Service',
                'schedule' => 'Schedule',
                'chemicals' => 'Chemicals',
                'time' => 'Time',
                'settings' => 'Settings',
                'login' => 'Login',
                'logout' => 'Logout',
                'phone' => 'Phone',
                'tablet' => 'Tablet',

                // Common
                'save' => 'Save',
                'cancel' => 'Cancel',
                'delete' => 'Delete',
                'edit' => 'Edit',
                'back' => 'Back',
                'submit' => 'Submit',
                'search' => 'Search',
                'loading' => 'Loading...',
                'no_data' => 'No data available',
                'success' => 'Success',
                'error' => 'Error',
                'confirm' => 'Confirm',
                'close' => 'Close',
                'view_details' => 'View Details',
                'status' => 'Status',
                'date' => 'Date',
                'notes' => 'Notes',
                'address' => 'Address',
                'total' => 'Total',
                'actions' => 'Actions',

                // Customer Home
                'welcome_back' => 'Welcome Back',
                'upcoming_services' => 'Upcoming Services',
                'recent_notifications' => 'Recent Notifications',
                'no_upcoming' => 'No upcoming services scheduled',
                'quick_actions' => 'Quick Actions',
                'weather' => 'Weather',

                // Estimates
                'estimate_number' => 'Estimate #',
                'pending' => 'Pending',
                'accepted' => 'Accepted',
                'declined' => 'Declined',
                'expired' => 'Expired',
                'approve' => 'Approve',
                'decline' => 'Decline',
                'valid_until' => 'Valid Until',
                'subtotal' => 'Subtotal',
                'tax' => 'Tax',
                'line_items' => 'Line Items',
                'make_payment' => 'Make Payment',
                'view_estimate' => 'View Estimate',

                // Jobs
                'scheduled' => 'Scheduled',
                'in_progress' => 'In Progress',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
                'job_details' => 'Job Details',
                'crew' => 'Crew',
                'property' => 'Property',
                'scheduled_date' => 'Scheduled Date',
                'completed_date' => 'Completed Date',
                'priority' => 'Priority',
                'high' => 'High',
                'medium' => 'Medium',
                'low' => 'Low',
                'send_message' => 'Send Message',
                'approve_job' => 'Approve Job',
                'messages' => 'Messages',

                // Profile
                'contact_info' => 'Contact Information',
                'first_name' => 'First Name',
                'last_name' => 'Last Name',
                'email' => 'Email',
                'phone_number' => 'Phone',
                'city' => 'City',
                'state' => 'State',
                'zip' => 'ZIP Code',
                'update_profile' => 'Update Profile',
                'change_password' => 'Change Password',
                'my_properties' => 'My Properties',

                // Request Service
                'request_estimate' => 'Request an Estimate',
                'service_type' => 'Service Type',
                'service_description' => 'Description',
                'preferred_date' => 'Preferred Date',
                'select_property' => 'Select Property',
                'add_property' => 'Add New Property',
                'submit_request' => 'Submit Request',
                'request_submitted' => 'Request submitted successfully!',

                // Employee - Schedule
                'todays_schedule' => "Today's Schedule",
                'route' => 'Route',
                'no_jobs_today' => 'No jobs scheduled for today',
                'start_route' => 'Start Route',
                'navigate' => 'Navigate',
                'job_count' => 'Jobs Today',

                // Employee - Chemicals
                'chemical_log' => 'Chemical Log',
                'add_entry' => 'Add Entry',
                'chemical_name' => 'Chemical Name',
                'epa_reg' => 'EPA Reg #',
                'target_pest' => 'Target Pest',
                'application_rate' => 'Application Rate',
                'area_treated' => 'Area Treated (sq ft)',
                'wind_speed' => 'Wind Speed (mph)',
                'temperature' => 'Temperature (°F)',
                'application_date' => 'Application Date',

                // Employee - Time
                'clock_in' => 'Clock In',
                'clock_out' => 'Clock Out',
                'current_shift' => 'Current Shift',
                'hours_today' => 'Hours Today',
                'break_time' => 'Break (min)',
                'time_log' => 'Time Log',
                'start_break' => 'Start Break',
                'end_break' => 'End Break',
                'shift_history' => 'Shift History',
                'no_active_shift' => 'No active shift',

                // Employee - Settings
                'language_setting' => 'Language',
                'english' => 'English',
                'spanish' => 'Spanish',
                'notifications_setting' => 'Notifications',
                'gps_tracking' => 'GPS Tracking',
                'dark_mode' => 'Dark Mode',
                'app_version' => 'App Version',
                'sign_out' => 'Sign Out',

                // Weather
                'current_weather' => 'Current Weather',
                'feels_like' => 'Feels like',
                'humidity' => 'Humidity',
                'wind' => 'Wind',

                // Upload
                'upload_photo' => 'Upload Photo',
                'upload_video' => 'Upload Video',
                'record_audio' => 'Record Audio Note',
                'attachments' => 'Attachments',
            ],
            'es' => [
                // Navigation
                'navigation' => 'Navegación',
                'home' => 'Inicio',
                'estimates' => 'Presupuestos',
                'jobs' => 'Trabajos',
                'profile' => 'Perfil',
                'request_service' => 'Solicitar Servicio',
                'schedule' => 'Horario',
                'chemicals' => 'Químicos',
                'time' => 'Tiempo',
                'settings' => 'Configuración',
                'login' => 'Iniciar Sesión',
                'logout' => 'Cerrar Sesión',
                'phone' => 'Teléfono',
                'tablet' => 'Tableta',

                // Common
                'save' => 'Guardar',
                'cancel' => 'Cancelar',
                'delete' => 'Eliminar',
                'edit' => 'Editar',
                'back' => 'Volver',
                'submit' => 'Enviar',
                'search' => 'Buscar',
                'loading' => 'Cargando...',
                'no_data' => 'No hay datos disponibles',
                'success' => 'Éxito',
                'error' => 'Error',
                'confirm' => 'Confirmar',
                'close' => 'Cerrar',
                'view_details' => 'Ver Detalles',
                'status' => 'Estado',
                'date' => 'Fecha',
                'notes' => 'Notas',
                'address' => 'Dirección',
                'total' => 'Total',
                'actions' => 'Acciones',

                // Customer Home
                'welcome_back' => 'Bienvenido',
                'upcoming_services' => 'Próximos Servicios',
                'recent_notifications' => 'Notificaciones Recientes',
                'no_upcoming' => 'No hay servicios programados',
                'quick_actions' => 'Acciones Rápidas',
                'weather' => 'Clima',

                // Estimates
                'estimate_number' => 'Presupuesto #',
                'pending' => 'Pendiente',
                'accepted' => 'Aceptado',
                'declined' => 'Rechazado',
                'expired' => 'Expirado',
                'approve' => 'Aprobar',
                'decline' => 'Rechazar',
                'valid_until' => 'Válido Hasta',
                'subtotal' => 'Subtotal',
                'tax' => 'Impuesto',
                'line_items' => 'Artículos',
                'make_payment' => 'Realizar Pago',
                'view_estimate' => 'Ver Presupuesto',

                // Jobs
                'scheduled' => 'Programado',
                'in_progress' => 'En Progreso',
                'completed' => 'Completado',
                'cancelled' => 'Cancelado',
                'job_details' => 'Detalles del Trabajo',
                'crew' => 'Equipo',
                'property' => 'Propiedad',
                'scheduled_date' => 'Fecha Programada',
                'completed_date' => 'Fecha Completada',
                'priority' => 'Prioridad',
                'high' => 'Alta',
                'medium' => 'Media',
                'low' => 'Baja',
                'send_message' => 'Enviar Mensaje',
                'approve_job' => 'Aprobar Trabajo',
                'messages' => 'Mensajes',

                // Profile
                'contact_info' => 'Información de Contacto',
                'first_name' => 'Nombre',
                'last_name' => 'Apellido',
                'email' => 'Correo',
                'phone_number' => 'Teléfono',
                'city' => 'Ciudad',
                'state' => 'Estado',
                'zip' => 'Código Postal',
                'update_profile' => 'Actualizar Perfil',
                'change_password' => 'Cambiar Contraseña',
                'my_properties' => 'Mis Propiedades',

                // Request Service
                'request_estimate' => 'Solicitar Presupuesto',
                'service_type' => 'Tipo de Servicio',
                'service_description' => 'Descripción',
                'preferred_date' => 'Fecha Preferida',
                'select_property' => 'Seleccionar Propiedad',
                'add_property' => 'Agregar Propiedad',
                'submit_request' => 'Enviar Solicitud',
                'request_submitted' => '¡Solicitud enviada exitosamente!',

                // Employee - Schedule
                'todays_schedule' => 'Horario de Hoy',
                'route' => 'Ruta',
                'no_jobs_today' => 'No hay trabajos programados para hoy',
                'start_route' => 'Iniciar Ruta',
                'navigate' => 'Navegar',
                'job_count' => 'Trabajos Hoy',

                // Employee - Chemicals
                'chemical_log' => 'Registro Químico',
                'add_entry' => 'Agregar Entrada',
                'chemical_name' => 'Nombre del Químico',
                'epa_reg' => '# Reg EPA',
                'target_pest' => 'Plaga Objetivo',
                'application_rate' => 'Tasa de Aplicación',
                'area_treated' => 'Área Tratada (pies²)',
                'wind_speed' => 'Velocidad del Viento (mph)',
                'temperature' => 'Temperatura (°F)',
                'application_date' => 'Fecha de Aplicación',

                // Employee - Time
                'clock_in' => 'Entrada',
                'clock_out' => 'Salida',
                'current_shift' => 'Turno Actual',
                'hours_today' => 'Horas Hoy',
                'break_time' => 'Descanso (min)',
                'time_log' => 'Registro de Tiempo',
                'start_break' => 'Iniciar Descanso',
                'end_break' => 'Terminar Descanso',
                'shift_history' => 'Historial de Turnos',
                'no_active_shift' => 'Sin turno activo',

                // Employee - Settings
                'language_setting' => 'Idioma',
                'english' => 'Inglés',
                'spanish' => 'Español',
                'notifications_setting' => 'Notificaciones',
                'gps_tracking' => 'Rastreo GPS',
                'dark_mode' => 'Modo Oscuro',
                'app_version' => 'Versión de la App',
                'sign_out' => 'Cerrar Sesión',

                // Weather
                'current_weather' => 'Clima Actual',
                'feels_like' => 'Sensación',
                'humidity' => 'Humedad',
                'wind' => 'Viento',

                // Upload
                'upload_photo' => 'Subir Foto',
                'upload_video' => 'Subir Video',
                'record_audio' => 'Grabar Nota de Audio',
                'attachments' => 'Archivos Adjuntos',
            ],
        ];

        return $translations[$this->language] ?? $translations['en'];
    }
}
