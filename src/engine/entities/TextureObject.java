package engine.entities;

/**
 * TextureObject - an entity with visual representation
 * Demonstrates composition with texture/sprite data
 */
public class TextureObject extends Entity {
    private Object texture;
    private String shaderType;
    private float scaleX;
    private float scaleY;
    private float rotation;
    
    public TextureObject(String texturePath, float x, float y, float scale) {
        super(x, y);
        this.texture = texturePath;  // Store as Object for Texture compatibility
        this.shaderType = "default";
        this.scaleX = scale;
        this.scaleY = scale;
        this.rotation = 0;
        this.width = 64;
        setHeight(64);
    }
    
    public Object getTexture() {
        return texture;
    }
    
    /**
     * Set the texture
     */
    public void setTexture(Object texture) {
        this.texture = texture;
    }
    
    /**
     * Set the shader type
     */
    public void setShaderType(String type) {
        this.shaderType = type;
    }
    
    public String getShaderType() {
        return shaderType;
    }
    
    public float getScaleX() {
        return scaleX;
    }
    
    public void setScaleX(float scaleX) {
        this.scaleX = scaleX;
    }
    
    public float getScaleY() {
        return scaleY;
    }
    
    public void setScaleY(float scaleY) {
        this.scaleY = scaleY;
    }
    
    public void setScale(float scale) {
        this.scaleX = scale;
        this.scaleY = scale;
    }
    
    public float getRotation() {
        return rotation;
    }
    
    public void setRotation(float rotation) {
        this.rotation = rotation;
    }
    
    @Override
    public void resetPosition() {
        // Reset to origin or initial position
        setPosition(0, 0);
        rotation = 0;
        scaleX = 1.0f;
        scaleY = 1.0f;
    }
    
    @Override
    public void update(float deltaTime) {
        if (!active) return;
        
        // Update position based on velocity
        x += velocity.x * deltaTime;
        y += velocity.y * deltaTime;
        position.set(x, y);
        
        // Could update animation frame, rotation, etc.
    }
    
    @Override
    public void render() {
        if (!active) return;
        
        // In a real implementation, this would render the texture
        System.out.println(String.format("Rendering TextureObject[%s] at (%.1f, %.1f) scale=(%.2f, %.2f) rotation=%.1f° shader=%s", 
            texture, x, y, scaleX, scaleY, rotation, shaderType));
    }
    
    @Override
    public String toString() {
        return String.format("TextureObject[texture=%s, x=%.2f, y=%.2f, scale=(%.2f,%.2f), shader=%s]", 
            texture, x, y, scaleX, scaleY, shaderType);
    }
}
