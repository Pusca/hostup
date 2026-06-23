import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import { Italian } from 'flatpickr/dist/l10n/it.js';

flatpickr.localize(Italian);

// Esposto per gli script inline delle pagine (es. landing immobile)
window.flatpickr = flatpickr;
