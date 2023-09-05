from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher
from typing import Any, Text, Dict, List

# This function simulates a database query for products in a specific location.
# You should replace this with your actual database query.
def get_products_in_location(location):
    # Replace this with your database query logic
    # Return a list of product dictionaries with 'name', 'price', and 'description' fields.
    # For this example, we'll use hardcoded data.
    products = [
        {"name": "Blue Shoes", "price": "$50", "description": "Comfortable blue shoes."},
        {"name": "Red T-shirt", "price": "$20", "description": "Stylish red t-shirt."},
        {"name": "Laptop", "price": "$800", "description": "High-performance laptop."},
    ]
    return products

# Placeholder function for calculating discounts (replace with your logic)
def calculate_discount(original_price):
    # In this example, we'll apply a 10% discount
    discount = 0.1
    discounted_price = original_price
    return f"${float(original_price.strip('$')) * (1 - discount):.2f}"

class ActionDisplayProductsInLocation(Action):
    def name(self) -> Text:
        return "action_display_products_in_location"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any]
    ) -> List[Dict[Text, Any]]:
        # Extract the "location" entity from the user's message
        location = next(tracker.get_latest_entity_values("location"), None)

        if location:
            # Connect to your product database and query for products in the specified location
            products = get_products_in_location(location)

            if products:
                # Display the products to the user with unique identifiers
                message = f"Here are the available products in {location}:"
                for i, product in enumerate(products):
                    message += f"\n{i+1}. {product['name']} ({product['price']})"
                dispatcher.utter_message(message)
            else:
                dispatcher.utter_message(f"No products found in {location}.")
        else:
            dispatcher.utter_message("I couldn't understand the location. Please specify a valid location.")

        return []

class ActionProvideProductDetails(Action):
    def name(self) -> Text:
        return "action_provide_product_details"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any]
    ) -> List[Dict[Text, Any]]:
        # Extract the user's selection (e.g., "product 2" or "second product")
        selected_product_text = tracker.latest_message.get("text")

        # Use NLP techniques to extract the identifier from the user's input.
        # For example, you can use regular expressions or a custom function.
        import re
        selected_product_match = re.search(r'\d+', selected_product_text)

        if selected_product_match:
            # Extract the selected product identifier (e.g., "2" for the second product)
            selected_product_index = int(selected_product_match.group())
            index = selected_product_index - 1

            if 0 <= index < len(products):
                selected_product = products[index]

                # Fetch and display more details about the selected product
                product_name = selected_product['name']
                product_price = selected_product['price']
                product_description = selected_product['description']
                message = f"Details for {product_name} (Price: {product_price}): {product_description}"
                dispatcher.utter_message(message)
            else:
                dispatcher.utter_message("Product not found.")
        else:
            dispatcher.utter_message("Please specify a valid product number.")

        return []

class ActionNegotiatePrice(Action):
    def name(self) -> Text:
        return "action_negotiate_price"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any]
    ) -> List[Dict[Text, Any]]:
        # Extract the user's selection (e.g., "product 2" or "second product")
        selected_product_text = tracker.latest_message.get("text")

        # Use NLP techniques to extract the identifier from the user's input.
        # For example, you can use regular expressions or a custom function.
        import re
        selected_product_match = re.search(r'\d+', selected_product_text)

        if selected_product_match:
            # Extract the selected product identifier (e.g., "2" for the second product)
            selected_product_index = int(selected_product_match.group())
            index = selected_product_index - 1

            if 0 <= index < len(products):
                selected_product = products[index]

                # Initiate the negotiation process for the selected product
                negotiated_price = calculate_discount(selected_product['price'])

                # Respond to the user with the negotiated price
                product_name = selected_product['name']
                message = f"I can offer {product_name} for {negotiated_price}. Would you like to proceed?"
                dispatcher.utter_message(message)
            else:
                dispatcher.utter_message("Product not found.")
        else:
            dispatcher.utter_message("Please specify a valid product number for negotiation.")

        return []
