package simulation;

import engine.core.GameMaster;
import engine.entities.Entity;
import engine.output.Speaker;
import engine.output.SoundOut;
import java.util.List;

public class EngineSimulation {
    private GameMaster gameMaster;
    private Speaker speaker;
    private SoundOut soundOut;
    private boolean simulationRunning;
    private float simulationTime;
    private final float maxSimulationTime = 30.0f;
    
    public EngineSimulation() {
        this.gameMaster = new GameMaster();
        this.simulationRunning = false;
        this.simulationTime = 0;
    }
    
    public void initialize() {
        // Initialize the non-contextual foundation 
        gameMaster.initialize();
        simulationRunning = true;
        
        // Get speaker and SoundOut interface
        speaker = gameMaster.getInputOutputManager().getSpeaker();
        soundOut = speaker; // Speaker implements SoundOut
        
        // Initialize sounds
        initializeSounds();
    }
    
    /**
     * Initialize sound system for simulation
     */
    private void initializeSounds() {
        System.out.println("EngineSimulation: Initializing sound system...");
        
        // Load sounds using SoundOut interface
        soundOut.loadSound("demo", "sounds/demo.wav");
        soundOut.loadSound("background", "sounds/background.mp3");
        soundOut.setLooping("background", true);
        
        // Demonstrate SoundOut interface methods
        System.out.println("EngineSimulation: Loaded " + soundOut.getSoundCount() + " sounds");
        
        // Play background music at low volume
        soundOut.play("background", 0.2f);
    }
    
    public void run() {
        if (!gameMaster.isInitialized()) return;
        
        gameMaster.start();
        
        while (simulationRunning && simulationTime < maxSimulationTime) {
            // Centralized update handles TimeManager and all Must-Haves
            gameMaster.update(); 
            
            // Retrieve Delta Time from the engine's TimeManager
            float dt = gameMaster.getTimeManager().getDeltaTime();
            simulationTime += dt;
            
            // Demonstrate TimeManager.getTotalTime() method
            if (simulationTime > 1.0f && simulationTime < 1.1f) {
                float totalTime = gameMaster.getTimeManager().getTotalTime();
                System.out.println("TimeManager: Total time elapsed: " + totalTime + "s");
                
                // Demonstrate sound playback
                soundOut.play("demo", 0.5f);
                System.out.println("EngineSimulation: Playing demo sound via SoundOut interface");
            }
            
            // Simulate user input via the InputOutputManager 
            if (simulationTime > 2.0f && simulationTime < 5.0f) {
                gameMaster.getInputOutputManager().getKeyboard().keyDown(68);
            } else {
                gameMaster.getInputOutputManager().getKeyboard().keyUp(68);
            }
            
            // Demonstrate EntityManager methods
            if (simulationTime > 1.0f && simulationTime < 1.1f) {
                List<Entity> entities = gameMaster.getEntityManager().getEntities();
                System.out.println("EntityManager: Found " + entities.size() + " entities");
                
                // Demonstrate SoundOut interface - check if sound is playing
                if (soundOut.isPlaying("background")) {
                    System.out.println("EngineSimulation: Background music is playing");
                }
            }
            
            // Demonstrate sound control at different times
            if (simulationTime > 10.0f && simulationTime < 10.1f) {
                System.out.println("EngineSimulation: Pausing background music");
                soundOut.pause("background");
            }
            
            if (simulationTime > 15.0f && simulationTime < 15.1f) {
                System.out.println("EngineSimulation: Resuming background music");
                soundOut.resume("background");
            }
            
            // Render call delegates to the SceneManager
            gameMaster.render(null);
            
            try {
                Thread.sleep(16); // Maintain ~60 FPS
            } catch (InterruptedException e) {
                e.printStackTrace();
            }
            
            if (!gameMaster.isRunning()) break;
        }
        
        // Cleanup sounds
        System.out.println("EngineSimulation: Stopping all sounds");
        soundOut.stopAll();
        soundOut.unloadAll();
        
        gameMaster.dispose();
    }

    public static void main(String[] args) {
        EngineSimulation sim = new EngineSimulation();
        sim.initialize();
        sim.run();
    }
}