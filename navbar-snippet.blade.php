{{--
 DANS resources/views/components/navbar.blade.php :
 Remplace TOUT le bloc « Notifications » (le <div x-data="{ open: false }" class="relative"> qui contient
 la cloche statique et le panneau « Aucune notification ») par cette seule ligne :
--}}
<livewire:notification-bell />
