import random

print("🎮 Welcome to Stone - Paper - Scissor Game!")
print("---------------------------------------------")
print("👉 Type 's'  for 🪨 Stone")
print("👉 Type 'p'  for 📄 Paper")
print("👉 Type 'sc' for ✂️ Scissor")
print("---------------------------------------------")

# Mapping choices
choices = {"s": "🪨  Stone", "p": "📄  Paper", "sc": "✂️  Scissor"}

# Scores
user_score = 0
computer_score = 0
draws = 0
rounds = 0

# Play 5 rounds
while rounds < 5:
    you = input("\nEnter your choice: ").lower()

    if you not in choices:
        print("⚠️ Invalid choice! Try again (s, p, or sc).")
        continue

    # Random computer choice
    computer = random.choice(["s", "p", "sc"])

    print(f"\nYou chose: {choices[you]}")
    print(f"Computer chose: {choices[computer]}")

    # Simple winning logic
    if you == computer:
        print("😐 It's a Draw!")
        draws += 1
    elif (you == "s" and computer == "sc") or \
         (you == "p" and computer == "s") or \
         (you == "sc" and computer == "p"):
        print(random.choice(["🎉 You Win!", "🔥 Great Job!", "🏆 You’re on fire!"]))
        user_score += 1
    else:
        print(random.choice(["💻 Computer Wins!", "😅 Better luck next time!", "🤖 Try again!"]))
        computer_score += 1

    rounds += 1
    print(f"📊 Score → You: {user_score} | Computer: {computer_score} | Draws: {draws}")

# Final result
print("\n---------------------------------------------")
print("🏁 Game Over! Final Result:")
print(f"🧍 You: {user_score}")
print(f"💻 Computer: {computer_score}")
print(f"😐 Draws: {draws}")
print("---------------------------------------------")

if user_score > computer_score:
    print("🏆 You are the Champion! 🎉")
elif user_score < computer_score:
    print("🤖 Computer Wins the Game! Try again later 😅")
else:
    print("😐 It's a Tie! That was close.")
