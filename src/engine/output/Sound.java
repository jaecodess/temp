package engine.output;

import javax.sound.sampled.Clip;

/**
 * Represents a sound resource
 * Can be played, paused, stopped, and looped
 */
public class Sound {
    private String name;
    private String path;
    private Clip clip; // Audio clip for playback
    private boolean looping;
    private float volume;
    private boolean playing;
    private long duration; // Duration in milliseconds
    
    public Sound(String name, String path) {
        this.name = name;
        this.path = path;
        this.clip = null; // Will be loaded by Speaker
        this.looping = false;
        this.volume = 1.0f;
        this.playing = false;
        this.duration = 0;
    }
    
    /**
     * Get the sound name
     */
    public String getName() {
        return name;
    }
    
    /**
     * Set the sound name
     */
    public void setName(String name) {
        this.name = name;
    }
    
    /**
     * Get the sound file path
     */
    public String getPath() {
        return path;
    }
    
    /**
     * Set the sound file path
     */
    public void setPath(String path) {
        this.path = path;
    }
    
    /**
     * Get the audio clip
     */
    public Clip getClip() {
        return clip;
    }
    
    /**
     * Set the audio clip
     */
    public void setClip(Clip clip) {
        this.clip = clip;
        if (clip != null) {
            this.duration = clip.getMicrosecondLength() / 1000; // Convert to milliseconds
        }
    }
    
    /**
     * Check if sound is looping
     */
    public boolean isLooping() {
        return looping;
    }
    
    /**
     * Set whether sound should loop
     */
    public void setLooping(boolean looping) {
        this.looping = looping;
    }
    
    /**
     * Get the volume for this sound (0.0 to 1.0)
     */
    public float getVolume() {
        return volume;
    }
    
    /**
     * Set the volume for this sound (0.0 to 1.0)
     */
    public void setVolume(float volume) {
        this.volume = Math.max(0.0f, Math.min(1.0f, volume));
    }
    
    /**
     * Check if sound is currently playing
     */
    public boolean isPlaying() {
        return playing;
    }
    
    /**
     * Set playing state
     */
    public void setPlaying(boolean playing) {
        this.playing = playing;
    }
    
    /**
     * Get sound duration in milliseconds
     */
    public long getDuration() {
        return duration;
    }
    
    /**
     * Set sound duration in milliseconds
     */
    public void setDuration(long duration) {
        this.duration = duration;
    }
    
    @Override
    public String toString() {
        return String.format("Sound[name=%s, path=%s, looping=%b, volume=%.2f, playing=%b]", 
            name, path, looping, volume, playing);
    }
}

