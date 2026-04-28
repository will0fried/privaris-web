import { Controller } from '@hotwired/stimulus';

/*
 * Minimal audio player (podcast) — brand-consistent, Apple-like minimalism.
 *
 * Usage:
 *   <div data-controller="audio-player">
 *     <audio data-audio-player-target="audio" src="/path/to/episode.mp3" preload="metadata"></audio>
 *     <button data-audio-player-target="playBtn" data-action="click->audio-player#toggle">Play</button>
 *     <input type="range" data-audio-player-target="seek" data-action="input->audio-player#seek">
 *     <span data-audio-player-target="currentTime">0:00</span>
 *     <span data-audio-player-target="duration">0:00</span>
 *   </div>
 */
export default class extends Controller {
    static targets = ['audio', 'playBtn', 'seek', 'currentTime', 'duration'];

    connect() {
        this.audioTarget.addEventListener('timeupdate', () => this.updateProgress());
        this.audioTarget.addEventListener('loadedmetadata', () => this.updateDuration());
        this.audioTarget.addEventListener('ended', () => this.onEnded());
    }

    toggle() {
        if (this.audioTarget.paused) {
            this.audioTarget.play();
            this.playBtnTarget.textContent = '❚❚';
        } else {
            this.audioTarget.pause();
            this.playBtnTarget.textContent = '▶';
        }
    }

    seek(event) {
        const pct = event.target.value / 100;
        this.audioTarget.currentTime = this.audioTarget.duration * pct;
    }

    updateProgress() {
        if (!this.audioTarget.duration) return;
        const pct = (this.audioTarget.currentTime / this.audioTarget.duration) * 100;
        if (this.hasSeekTarget) this.seekTarget.value = pct;
        if (this.hasCurrentTimeTarget) this.currentTimeTarget.textContent = this._format(this.audioTarget.currentTime);
    }

    updateDuration() {
        if (this.hasDurationTarget) this.durationTarget.textContent = this._format(this.audioTarget.duration);
    }

    onEnded() {
        this.playBtnTarget.textContent = '▶';
        if (this.hasSeekTarget) this.seekTarget.value = 0;
    }

    _format(seconds) {
        if (!isFinite(seconds)) return '0:00';
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60).toString().padStart(2, '0');
        return `${m}:${s}`;
    }
}
