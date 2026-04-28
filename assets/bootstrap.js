import { startStimulusApp } from '@symfony/stimulus-bundle';
import * as Turbo from '@hotwired/turbo';

// Turbo Drive : navigation instantanée entre pages.
// On laisse le cache désactivé sur les pages dynamiques (back, flash messages).
Turbo.session.drive = true;

// Progress bar Turbo : délai court pour un feeling plus snappy.
Turbo.setProgressBarDelay(120);

const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
