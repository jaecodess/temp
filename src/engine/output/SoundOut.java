package engine.output;

/**
 * SoundOut interface - defines the contract for sound output operations
 * Based on UML diagram specification for sound output functionality
 */
public interface SoundOut {
    /**
     * Load a sound from a file path
     * @param name Unique name for the sound
     * @param path File path to the sound resource
     * @return The loaded Sound object
     */
    Sound loadSound(String name, String path);
    
    /**
     * Play a sound by name
     * @param name Name of the sound to play
     */
    void play(String name);
    
    /**
     * Play a sound with specific volume
     * @param name Name of the sound to play
     * @param volume Volume from 0.0 to 1.0
     */
    void play(String name, float volume);
    
    /**
     * Stop a sound by name
     * @param name Name of the sound to stop
     */
    void stop(String name);
    
    /**
     * Pause a sound by name
     * @param name Name of the sound to pause
     */
    void pause(String name);
    
    /**
     * Resume a paused sound
     * @param name Name of the sound to resume
     */
    void resume(String name);
    
    /**
     * Set whether a sound should loop
     * @param name Name of the sound
     * @param looping Whether to loop
     */
    void setLooping(String name, boolean looping);
    
    /**
     * Get a sound by name
     * @param name Name of the sound
     * @return The Sound object, or null if not found
     */
    Sound getSound(String name);
    
    /**
     * Check if a sound is currently playing
     * @param name Name of the sound
     * @return true if playing
     */
    boolean isPlaying(String name);
    
    /**
     * Stop all sounds
     */
    void stopAll();
    
    /**
     * Unload a sound
     * @param name Name of the sound to unload
     */
    void unloadSound(String name);
    
    /**
     * Unload all sounds
     */
    void unloadAll();
    
    /**
     * Get the number of loaded sounds
     * @return Number of loaded sounds
     */
    int getSoundCount();
}

