import './bootstrap';
import Alpine from 'alpinejs';
import vocabularyPlayer from './components/vocabularyPlayer';
import writingAiApp from './components/writingAiApp';
import vocabularyLearning from './components/vocabularyLearning';
import matchingGame from './components/matchingGame';
import { bootAnimations } from './utils/animations';

// Đăng ký các Alpine components dùng chung
Alpine.data('vocabularyPlayer', vocabularyPlayer);
Alpine.data('writingAiApp', writingAiApp);
Alpine.data('vocabularyLearning', vocabularyLearning);
Alpine.data('matchingGame', matchingGame);

window.Alpine = Alpine;

Alpine.start();

// Khởi động hệ thống animation UI
document.addEventListener('DOMContentLoaded', bootAnimations);
