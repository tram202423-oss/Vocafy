import './bootstrap';
import Alpine from 'alpinejs';
import vocabularyPlayer from './components/vocabularyPlayer';

// Đăng ký các Alpine components dùng chung
Alpine.data('vocabularyPlayer', vocabularyPlayer);

window.Alpine = Alpine;

Alpine.start();
