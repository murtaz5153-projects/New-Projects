from openai import OpenAI
 
# pip install openai 
# if you saved the key under a different environment variable name, you can do something like:
client = OpenAI(
  api_key="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",
)

completion = client.chat.completions.create(
  model="gpt-3.5-turbo",
  messages=[
    {"role": "system", "content": "You are a virtual assistant named jarvis skilled in general tasks like Alexa and Google Cloud"},
    {"role": "user", "content": "what is coding"}
  ]
)

print(completion.choices[0].message.content)
# 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
