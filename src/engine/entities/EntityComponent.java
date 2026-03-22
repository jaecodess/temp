package engine.entities;

/**
 * Base class for entity components
 * Components can be added to entities to provide additional functionality
 */
public abstract class EntityComponent {
    protected String type;
    
    public EntityComponent(String type) {
        this.type = type;
    }
    
    public String getType() {
        return type;
    }
    
    /**
     * Update the component
     */
    public abstract void update(float deltaTime);
    
    /**
     * Render the component
     */
    public abstract void render(Object batch, Object shape);
}

