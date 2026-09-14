import './bootstrap';
import Alpine from 'alpinejs';
import vocabularyPlayer from './components/vocabularyPlayer';
import writingAiApp from './components/writingAiApp';
import vocabularyLearning from './components/vocabularyLearning';

// Đăng ký các Alpine components dùng chung
Alpine.data('vocabularyPlayer', vocabularyPlayer);
Alpine.data('writingAiApp', writingAiApp);
Alpine.data('vocabularyLearning', vocabularyLearning);

window.Alpine = Alpine;

Alpine.start();
