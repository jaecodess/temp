package engine.output;

import javax.sound.sampled.*;
import java.io.File;
import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.util.HashMap;
import java.util.Map;

/**
 * Speaker - Audio output device
 * Handles sound playback, loading, and management
 * Extends OutputProcessor for consistent output device interface
 * Implements SoundOut interface for sound output operations
 */
public class Speaker extends OutputProcessor implements SoundOut {
    private Map<String, Sound> sounds;
    private boolean muted;
    
    public Speaker() {
        super();
        this.sounds = new HashMap<>();
        this.muted = false;
    }
    
    @Override
    public void process() {
        // Update sound states, handle looping, etc.
        // This would be called each frame to manage audio
    }
    
    /**
     * Load a sound from a file path
     * @param name Unique name for the sound
     * @param path File path to the sound resource
     * @return The loaded Sound object
     */
    public Sound loadSound(String name, String path) {
        Sound sound = new Sound(name, path);
        
        try {
            // Try to load the audio file
            File audioFile = new File(path);
            if (audioFile.exists()) {
                AudioInputStream audioStream = AudioSystem.getAudioInputStream(audioFile);
                Clip clip = AudioSystem.getClip();
                clip.open(audioStream);
                sound.setClip(clip);
                System.out.println("Speaker: Loaded sound '" + name + "' from " + path);
            } else {
                // File doesn't exist - generate a simple beep for demo
                System.out.println("Speaker: Sound file not found: " + path + " (generating beep)");
                sound.setClip(generateBeep());
            }
        } catch (Exception e) {
            // If file doesn't exist or can't be loaded, generate a beep
            System.out.println("Speaker: Could not load sound '" + name + "' from " + path + 
                " - " + e.getMessage() + " (generating beep)");
            try {
                sound.setClip(generateBeep());
            } catch (Exception ex) {
                System.out.println("Speaker: Could not generate beep: " + ex.getMessage());
            }
        }
        
        sounds.put(name, sound);
        return sound;
    }
    
    /**
     * Play a sound by name
     * @param name Name of the sound to play
     */
    public void play(String name) {
        play(name, 1.0f);
    }
    
    /**
     * Play a sound with specific volume
     * @param name Name of the sound to play
     * @param volume Volume from 0.0 to 1.0
     */
    public void play(String name, float volume) {
        if (!enabled || muted) return;
        
        Sound sound = sounds.get(name);
        if (sound != null && sound.getClip() != null) {
            try {
                Clip clip = sound.getClip();
                
                // Stop if already playing
                if (clip.isRunning()) {
                    clip.stop();
                }
                
                // Reset to beginning
                clip.setFramePosition(0);
                
                // Set volume
                sound.setVolume(volume);
                float finalVolume = this.volume * volume;
                setClipVolume(clip, finalVolume);
                
                // Set looping
                if (sound.isLooping()) {
                    clip.loop(Clip.LOOP_CONTINUOUSLY);
                } else {
                    clip.start();
                }
                
                sound.setPlaying(true);
                System.out.println("Speaker: Playing sound '" + name + "' at volume " + finalVolume);
            } catch (Exception e) {
                System.out.println("Speaker: Error playing sound '" + name + "': " + e.getMessage());
            }
        } else if (sound != null) {
            // Sound exists but no clip loaded - just mark as playing for demo
            sound.setVolume(volume);
            sound.setPlaying(true);
            System.out.println("Speaker: Playing sound '" + name + "' (placeholder) at volume " + 
                (this.volume * volume));
        }
    }
    
    /**
     * Set volume for a clip using FloatControl
     */
    private void setClipVolume(Clip clip, float volume) {
        try {
            FloatControl gainControl = (FloatControl) clip.getControl(FloatControl.Type.MASTER_GAIN);
            float range = gainControl.getMaximum() - gainControl.getMinimum();
            float gain = (range * volume) + gainControl.getMinimum();
            gainControl.setValue(gain);
        } catch (Exception e) {
            // Volume control not available for this clip
        }
    }
    
    /**
     * Stop a sound by name
     * @param name Name of the sound to stop
     */
    public void stop(String name) {
        Sound sound = sounds.get(name);
        if (sound != null) {
            if (sound.getClip() != null) {
                Clip clip = sound.getClip();
                if (clip.isRunning()) {
                    clip.stop();
                }
                clip.setFramePosition(0);
            }
            sound.setPlaying(false);
            System.out.println("Speaker: Stopping sound '" + name + "'");
        }
    }
    
    /**
     * Pause a sound by name
     * @param name Name of the sound to pause
     */
    public void pause(String name) {
        Sound sound = sounds.get(name);
        if (sound != null && sound.getClip() != null) {
            Clip clip = sound.getClip();
            if (clip.isRunning()) {
                clip.stop();
                System.out.println("Speaker: Pausing sound '" + name + "'");
            }
        } else if (sound != null && sound.isPlaying()) {
            System.out.println("Speaker: Pausing sound '" + name + "' (placeholder)");
        }
    }
    
    /**
     * Resume a paused sound
     * @param name Name of the sound to resume
     */
    public void resume(String name) {
        Sound sound = sounds.get(name);
        if (sound != null && sound.getClip() != null) {
            Clip clip = sound.getClip();
            if (!clip.isRunning()) {
                if (sound.isLooping()) {
                    clip.loop(Clip.LOOP_CONTINUOUSLY);
                } else {
                    clip.start();
                }
                sound.setPlaying(true);
                System.out.println("Speaker: Resuming sound '" + name + "'");
            }
        } else if (sound != null && !sound.isPlaying()) {
            sound.setPlaying(true);
            System.out.println("Speaker: Resuming sound '" + name + "' (placeholder)");
        }
    }
    
    /**
     * Set whether a sound should loop
     * @param name Name of the sound
     * @param looping Whether to loop
     */
    public void setLooping(String name, boolean looping) {
        Sound sound = sounds.get(name);
        if (sound != null) {
            sound.setLooping(looping);
        }
    }
    
    /**
     * Get a sound by name
     * @param name Name of the sound
     * @return The Sound object, or null if not found
     */
    public Sound getSound(String name) {
        return sounds.get(name);
    }
    
    /**
     * Check if a sound is currently playing
     * @param name Name of the sound
     * @return true if playing
     */
    public boolean isPlaying(String name) {
        Sound sound = sounds.get(name);
        return sound != null && sound.isPlaying();
    }
    
    /**
     * Stop all sounds
     */
    public void stopAll() {
        for (Sound sound : sounds.values()) {
            if (sound.getClip() != null) {
                Clip clip = sound.getClip();
                if (clip.isRunning()) {
                    clip.stop();
                }
                clip.setFramePosition(0);
            }
            sound.setPlaying(false);
        }
        System.out.println("Speaker: Stopped all sounds");
    }
    
    /**
     * Unload a sound
     * @param name Name of the sound to unload
     */
    public void unloadSound(String name) {
        Sound sound = sounds.get(name);
        if (sound != null) {
            if (sound.isPlaying()) {
                stop(name);
            }
            if (sound.getClip() != null) {
                sound.getClip().close();
            }
            sounds.remove(name);
            System.out.println("Speaker: Unloaded sound '" + name + "'");
        }
    }
    
    /**
     * Unload all sounds
     */
    public void unloadAll() {
        stopAll();
        sounds.clear();
        System.out.println("Speaker: Unloaded all sounds");
    }
    
    /**
     * Get the number of loaded sounds
     */
    public int getSoundCount() {
        return sounds.size();
    }
    
    @Override
    public void mute() {
        this.muted = true;
        stopAll();
        System.out.println("Speaker: Muted");
    }
    
    @Override
    public void unmute() {
        this.muted = false;
        System.out.println("Speaker: Unmuted");
    }
    
    @Override
    public boolean isMuted() {
        return muted;
    }
    
    @Override
    public void setVolume(float volume) {
        super.setVolume(volume);
        System.out.println("Speaker: Volume set to " + volume);
    }
    
    /**
     * Generate a simple beep sound for testing/demo purposes
     */
    private Clip generateBeep() throws LineUnavailableException, IOException {
        int sampleRate = 22050; // Higher quality sample rate
        int duration = 150; // Shorter duration for snappier sound
        int numSamples = duration * sampleRate / 1000;
        byte[] buffer = new byte[numSamples];
        
        // Generate a more pleasant tone with envelope (fade in/out)
        double frequency = 523.25; // C5 note (more pleasant than A4)
        double amplitude = 0.5; // Lower amplitude for softer sound
        
        for (int i = 0; i < numSamples; i++) {
            double angle = 2.0 * Math.PI * i * frequency / sampleRate;
            
            // Apply envelope (fade in and fade out) for smoother sound
            double envelope;
            double progress = (double) i / numSamples;
            if (progress < 0.1) {
                // Fade in
                envelope = progress / 0.1;
            } else if (progress > 0.9) {
                // Fade out
                envelope = (1.0 - progress) / 0.1;
            } else {
                // Full volume in middle
                envelope = 1.0;
            }
            
            // Generate sine wave with envelope
            double sample = Math.sin(angle) * amplitude * envelope;
            buffer[i] = (byte) (sample * 127);
        }
        
        // Create audio format with higher quality
        AudioFormat format = new AudioFormat(sampleRate, 8, 1, true, false);
        ByteArrayInputStream bais = new ByteArrayInputStream(buffer);
        AudioInputStream audioStream = new AudioInputStream(bais, format, buffer.length);
        
        // Create and open clip
        Clip clip = AudioSystem.getClip();
        clip.open(audioStream);
        
        return clip;
    }
    
    /**
     * Play a simple system beep
     */
    public void beep() {
        if (!enabled || muted) return;
        try {
            // Generate actual beep sound using Clip
            Clip clip = generateBeep();
            clip.start();
        } catch (Exception e) {
            // Fallback to system beep
            java.awt.Toolkit.getDefaultToolkit().beep();
        }
    }

    /**
     * Dispose of all resources
     */
    public void dispose() {
        unloadAll();
        System.out.println("Speaker: Disposed");
    }
}
