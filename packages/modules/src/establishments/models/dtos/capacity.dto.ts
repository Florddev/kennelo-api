export type AnimalTypeDto = {
    id: number;
    code: string;
    name: string;
    category: string;
};

export type CapacityDto = {
    id: number;
    animal_type: AnimalTypeDto;
    max_capacity: number;
    price_per_night: string;
    occupied_spots: number;
    available_spots: number;
};
