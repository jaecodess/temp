package nutriquest;

import java.util.Random;

/**
 * Observer pattern: Listens to timer events to trigger quizzes.
 * Correct answers grant bonus time.
 */
public class QuizManager {
    private static final String[][] QUIZ_QUESTIONS = {
        {"Which food group does rice belong to?", "Grains", "Fruits", "Vegetables", "Dairy"},
        {"Which food group does apple belong to?", "Fruits", "Vegetables", "Grains", "Protein"},
        {"Which food group does broccoli belong to?", "Vegetables", "Fruits", "Grains", "Dairy"},
        {"Which food is healthiest for breakfast?", "Oatmeal", "Donut", "Candy", "Soda"},
        {"Which provides lasting energy?", "Whole grains", "Sugary snacks", "Soda", "Candy"}
    };

    private float lastQuizTime;
    private float quizInterval;
    private boolean quizActive;
    private String currentQuestion;
    private String correctAnswer;
    private String[] choices;
    private Random rnd;

    public QuizManager() {
        this.lastQuizTime = 0;
        this.quizInterval = 15.0f;
        this.quizActive = false;
        this.rnd = new Random();
    }

    /**
     * Called each update - observer of timer. Triggers quiz at intervals.
     * @param elapsedTime Total time elapsed in level
     * @return Bonus seconds to add if quiz was just answered correctly, else 0
     */
    public float onTimeUpdate(float elapsedTime) {
        if (quizActive) return 0;

        if (elapsedTime - lastQuizTime >= quizInterval) {
            lastQuizTime = elapsedTime;
            startQuiz();
        }
        return 0;
    }

    private void startQuiz() {
        quizActive = true;
        int idx = rnd.nextInt(QUIZ_QUESTIONS.length);
        String[] q = QUIZ_QUESTIONS[idx];
        currentQuestion = q[0];
        correctAnswer = q[1];
        choices = new String[]{q[1], q[2], q[3], q[4]};
        shuffle(choices);
    }

    private void shuffle(String[] arr) {
        for (int i = arr.length - 1; i > 0; i--) {
            int j = rnd.nextInt(i + 1);
            String t = arr[i];
            arr[i] = arr[j];
            arr[j] = t;
        }
    }

    /**
     * Submit quiz answer. Returns bonus seconds if correct.
     */
    public float submitAnswer(String answer) {
        if (!quizActive) return 0;
        quizActive = false;
        if (answer != null && answer.equalsIgnoreCase(correctAnswer)) {
            return 10.0f; // Bonus time
        }
        return 0;
    }

    public boolean isQuizActive() { return quizActive; }
    public String getCurrentQuestion() { return currentQuestion; }
    public String[] getChoices() { return choices; }
    public String getCorrectAnswer() { return correctAnswer; }
    public void setQuizInterval(float seconds) { this.quizInterval = seconds; }
}
