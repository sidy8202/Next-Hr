<?php

// À AJOUTER à la fin de routes/console.php (garde le reste du fichier tel quel) :

use Illuminate\Support\Facades\Schedule;

// Chaque entreprise est traitée à partir de 7 h dans SON fuseau horaire ; la commande est idempotente.
Schedule::command('app:send-birthday-notifications')->hourly();
