import './bootstrap';
import Alpine from 'alpinejs';
import vocabularyPlayer from './components/vocabularyPlayer';
import writingAiApp from './components/writingAiApp';

// Đăng ký các Alpine components dùng chung
Alpine.data('vocabularyPlayer', vocabularyPlayer);
Alpine.data('writingAiApp', writingAiApp);

window.Alpine = Alpine;

Alpine.start();

